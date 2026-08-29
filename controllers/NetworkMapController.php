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
			//сопоставление пишет в карточку оборудования - это правка данных
			static::PERM_EDIT => ['assign'],
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
	 * Запрос синхронный: сервис держит его до готовности либо своего
	 * scan.deadline, по которому возвращает собранное с перечнем неуспевших.
	 * Никаких id задач и повторных попыток: не уложились - ошибка с
	 * диагностикой, тайминги согласуются конфигами (tableWait > deadline).
	 *
	 * Последний результат держится в кэше приложения недолго
	 * ({@see LAST_SCAN_TTL}): после записи одной связи карта пересобирается
	 * с тем же опросом (`reuse=1`), а не гоняет всю площадку заново ради
	 * одной галочки. Это не хранение результатов - штамп опроса на экране
	 * говорит, насколько данные свежие.
	 *
	 * GET: place (int), reuse (int) - взять последний опрос из кэша, если он
	 *   ещё есть.
	 * Ответ: HTML карты со слоем либо JSON {status: error}.
	 */
	public function actionScan(int $place, int $reuse = 0, int $rooms = 0)
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

		//синхронная модель: один запрос, сервис держит его до готовности либо
		//своего scan.deadline (по нему придёт собранное с перечнем неуспевших).
		//pending = не уложились в tableWait - это ошибка с диагностикой, а не
		//повод опрашивать повторно: никаких id задач и попыток
		if (($data['status'] ?? null) === 'pending') {
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ['status' => 'error', 'error' => 'опрос не уложился в отведённое время: '
				.'tableWait интеграции должен покрывать scan.deadline сервиса; '
				.'кто тормозит - в журнале сервиса, строки «полный опрос занял»'];
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

	/**
	 * Сопоставить неопознанного соседа с карточкой коммутатора.
	 *
	 * Сверка опознаёт соседей по hostname/имени/адресу; когда коммутатор в
	 * инвентаризации есть, но эти поля пусты, чинить надо карточку - и делать
	 * это не покидая карты. Дописываем то, чем сосед представился: имя - в
	 * пустой hostname, адрес - в список MAC (если его там нет). Ничего не
	 * перезаписываем: заполненный чужим именем hostname - повод разобраться
	 * руками, а не молча затереть.
	 *
	 * POST: tech (int) - карточка; name (string) - имя из LLDP; mac (string).
	 * @return array JSON {status: ok|error, error?}
	 */
	public function actionAssign()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		$request = Yii::$app->request;

		$tech = \app\models\Techs::findOne((int)$request->post('tech'));
		if (!is_object($tech)) return ['status' => 'error', 'error' => 'не выбрано оборудование'];

		$name = trim((string)$request->post('name'));
		//LLDP печатает то короткое имя, то FQDN - в hostname пишем как есть,
		//опознание умеет отбрасывать домен
		$mac = trim((string)$request->post('mac'));

		$written = [];
		$warning = '';

		if (strlen($name)) {
			if (strlen(trim((string)$tech->hostname))) {
				$warning = 'hostname уже заполнен ('.$tech->hostname.') - имя не трогаю';
			} else {
				//hostname без домена не проходит валидацию, а LLDP домен сообщает
				//не всегда: короткому имени подставляем домен по умолчанию - та
				//же конвенция, что у новых записей (params['domains.default']).
				//Домена нет ни у карточки, ни в справочнике - имя не пишем, а
				//говорим почему: молча записать невалидное хуже
				$domainId = $tech->domain_id;
				if (!$domainId && strpos($name, '.') === false) {
					$domainId = \app\models\Domains::findByName(
						(string)(Yii::$app->params['domains.default'] ?? ''));
				}
				if ($domainId || strpos($name, '.') !== false) {
					$tech->domain_id = $domainId ?: $tech->domain_id;
					$tech->hostname = $name;
					$written[] = 'hostname';
				} else {
					$warning = 'имя не записать: у карточки нет домена, а домен по умолчанию «'
						.(Yii::$app->params['domains.default'] ?? '')
						.'» не заведён - впишите hostname в карточку руками';
				}
			}
		}
		if (strlen($mac)) {
			$hex = MacSearchProvider::hexMac($mac);
			if ($hex && strpos(strtolower((string)$tech->mac), strtolower($hex)) === false) {
				$tech->mac = trim((string)$tech->mac."\n".$mac);
				$written[] = 'MAC';
			}
		}
		if (!count($written)) {
			return ['status' => 'error', 'error' => $warning
				?: 'соседу нечем представиться (нет ни имени, ни адреса)'];
		}
		if (!$tech->save()) {
			//имя не прошло валидацию (домен из FQDN не заведён и т.п.) - но
			//MAC записать всё ещё можно: лучше полшага, чем отказ целиком
			if (in_array('hostname', $written, true) && count($written) > 1) {
				$error = implode('; ', $tech->firstErrors);
				$tech->refresh();
				$hex = MacSearchProvider::hexMac($mac);
				$tech->mac = trim((string)$tech->mac."\n".$mac);
				if ($tech->save()) {
					return ['status' => 'ok', 'written' => ['MAC'],
						'warning' => 'имя не записалось ('.$error.'), записан только MAC'];
				}
			}
			return ['status' => 'error', 'error' => implode('; ', $tech->firstErrors)];
		}
		return ['status' => 'ok', 'written' => $written, 'warning' => $warning];
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

	/**
	 * Acceptance: запись связи - POST с JSON-ответом; без выбранного
	 * оборудования отвечает ошибкой в теле, а не падает
	 */
	public function testAssign(): array
	{
		return [
			['name' => 'default', 'POST' => [], 'response' => 200],
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
