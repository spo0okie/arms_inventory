<?php

namespace app\controllers;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\MacSearchProvider;
use app\components\NetworkMap;
use app\models\Places;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Карта сети площадки: коммутаторы и связи между ними из записей
 * инвентаризации (docs/dev/network-map.md).
 *
 * Своей модели у страницы нет — она читает `techs` и `ports`; права на
 * просмотр — общие «чтение всего» (карта не раскрывает ничего сверх
 * карточек, на которые ведёт).
 */
class NetworkMapController extends ArmsBaseController
{
	public $modelClass = Places::class;

	/** сколько держать последний опрос площадки для пересборки карты после записи связи, сек */
	const LAST_SCAN_TTL = 900;

	public function accessMap()
	{
		return [
			static::PERM_VIEW => ['index', 'scan'],
		];
	}

	/** CRUD базового контроллера здесь не нужен */
	public function actions() {return [];}

	public function disabledActions()
	{
		return ['create', 'update', 'delete', 'item', 'item-by-name', 'ttip', 'validate', 'view',
			'async-grid', 'search', 'copy', 'unlink', 'editable'];
	}

	/**
	 * Карта площадки.
	 *
	 * GET:
	 * - place (int) — id площадки (корня дерева помещений); пусто — первая,
	 *   на которой есть коммутаторы, и селектор для остальных.
	 */
	public function actionIndex(int $place = 0, int $rooms = 0)
	{
		$sites = NetworkMap::sites();
		$site = null;
		if ($place) {
			$site = Places::findOne($place);
			if (!is_object($site)) throw new NotFoundHttpException('Площадка не найдена');
		} elseif (count($sites)) {
			$site = $sites[0];
		}

		$map = is_object($site) ? new NetworkMap($site) : null;
		if ($map) $map->groupByPlace = (bool)$rooms;
		return $this->render('index', [
			'sites' => $sites,
			'site' => $site,
			'map' => $map,
			'rooms' => (bool)$rooms,
			'provider' => static::provider(),
		]);
	}

	/**
	 * Сверка карты с сетью: соседи по LLDP/CDP со всех коммутаторов площадки.
	 *
	 * Один POST в сервис со всеми целями площадки (веер на его стороне),
	 * результат - слой поверх записанных рёбер ({@see NetworkMap::overlay()}).
	 * Ничего не хранится: опрос по кнопке, 20–70 коммутаторов укладываются в
	 * десятки секунд, а пока сервис работает - ответ pending, и страница
	 * перезапрашивает сама (тот же опрос по тому же ключу присоединяется к
	 * идущему, а не запускает второй).
	 *
	 * Последний результат держится в кэше приложения недолго
	 * ({@see LAST_SCAN_TTL}): после записи одной связи карта пересобирается
	 * с тем же опросом (`reuse=1`), а не гоняет всю площадку заново ради
	 * одной галочки. Это не хранение результатов - штамп опроса на экране
	 * говорит, насколько данные свежие.
	 *
	 * GET: place (int), attempt (int) - номер попытки при pending,
	 *   reuse (int) - взять последний опрос из кэша, если он ещё есть.
	 * Ответ: HTML карты со слоем либо JSON {status: pending|error}.
	 */
	public function actionScan(int $place, int $attempt = 0, int $reuse = 0, int $rooms = 0)
	{
		$site = Places::findOne($place);
		if (!is_object($site)) throw new NotFoundHttpException('Площадка не найдена');

		$provider = static::provider();
		if (!$provider) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ['status' => 'error', 'error' => 'интеграция macsearch не настроена'];
		}

		$cacheKey = ['network-map-scan', $site->id];
		$data = $reuse ? Yii::$app->cache->get($cacheKey) : false;
		if (!is_array($data)) {
			try {
				$data = $provider->siteNeighbors($site->id);
			} catch (\Throwable $e) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return ['status' => 'error', 'error' => $e->getMessage()];
			}
			if (($data['status'] ?? null) === 'done') Yii::$app->cache->set($cacheKey, $data, static::LAST_SCAN_TTL);
		}

		if (($data['status'] ?? null) === 'pending') {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$limit = (int)($provider->config['maxAttempts'] ?? MacSearchProvider::DEFAULT_MAX_ATTEMPTS);
			return ['status' => 'pending', 'attempt' => $attempt + 1, 'more' => $attempt + 1 < $limit];
		}
		if (($data['status'] ?? null) !== 'done') {
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ['status' => 'error', 'error' => $data['error'] ?? 'сервис ответил без результата'];
		}

		$map = new NetworkMap($site);
		$map->groupByPlace = (bool)$rooms;
		$map->overlay($data, $provider);
		return $this->renderAjax('_map', ['map' => $map, 'site' => $site, 'provider' => $provider,
			'scanStamp' => $provider->scanStamp($data)]);
	}

	/** Провайдер macsearch, если интеграция включена и доступна пользователю */
	public static function provider(): ?MacSearchProvider
	{
		foreach (IntegrationsRegistry::providers() as $provider) {
			if ($provider instanceof MacSearchProvider && IntegrationsRegistry::userCanView($provider)) return $provider;
		}
		return null;
	}

	/**
	 * Acceptance: страница открывается без площадки (первая по списку) и с
	 * площадкой из дампа; несуществующая — 404.
	 */
	public function testIndex(): array
	{
		$site = Places::find()->where(['parent_id' => null])->orderBy('id')->one();
		return [
			['name' => 'default', 'response' => 200],
			is_object($site)
				? ['name' => 'site', 'GET' => ['place' => $site->id], 'response' => 200]
				: ['name' => 'site', 'skip' => true, 'reason' => 'в дампе нет корневых помещений'],
			['name' => 'missing', 'GET' => ['place' => 999999999], 'response' => 404],
		];
	}

	/** Acceptance: без настроенной интеграции сверка отвечает JSON с ошибкой, а не падает */
	public function testScan(): array
	{
		$site = Places::find()->where(['parent_id' => null])->orderBy('id')->one();
		if (!is_object($site)) return self::skipScenario('default', 'в дампе нет корневых помещений');
		return [
			['name' => 'default', 'GET' => ['place' => $site->id], 'response' => 200],
			['name' => 'missing', 'GET' => ['place' => 999999999], 'response' => 404],
		];
	}
}
