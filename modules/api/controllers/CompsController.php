<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\CompsSearch;
use app\models\LoginJournal;
use PHPUnit\Framework\Assert;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use OpenApi\Attributes as OA;

class CompsController extends BaseRestController
{

	public function accessMap(): array
	{
		return array_merge_recursive(parent::accessMap(),[
			'update-comps'=>['push']
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		$behaviors['verbFilter']['actions']['push']=['POST'];
		$behaviors['verbFilter']['actions']['update']=['POST','PUT','PATCH'];
		return $behaviors;
	}

	public $modelClass='app\models\Comps';

	public static array $searchFields=[
		'name'=>'name',
		'ip'=>'ip',
		'mac'=>'mac',
	];

	/**
	 * Возвращает единственную запись компьютера, найденную по имени, домену или IP.
	 * Если передан `name` — делегирует в CompsController::searchModel() (поиск по hostname/FQDN).
	 * Иначе использует базовый фильтр по полям из static::$searchFields (name, ip, mac).
	 *
	 * GET-параметры:
	 * @param string|null $name    Имя компьютера или hostname
	 * @param string|null $domain  Домен (используется только при поиске через searchModel)
	 * @param string|null $ip      IP-адрес компьютера
	 *
	 * @return ActiveRecord|null
	 */
	public function actionSearch($name=null,$domain=null,$ip=null): ActiveRecord|null {
		if ($name) return \app\controllers\CompsController::searchModel($name,$domain,$ip);
		return parent::actionSearch();
	}

	/**
	 * Возвращает отфильтрованный список компьютеров через CompsSearch.
	 * Поддерживает параметр `showArchived` для включения архивных записей.
	 * Все остальные параметры фильтрации передаются через queryParams в CompsSearch::search().
	 *
	 * GET-параметры:
	 *   showArchived — bool, включить архивные записи (по умолчанию: false)
	 *   + прочие атрибуты CompsSearch
	 *
	 * @return ActiveDataProvider
	 */
	public function actionFilter(): ActiveDataProvider
	{
		$searchModel = new CompsSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		$params = Yii::$app->request->queryParams;
		return $searchModel->search($params);
    }

	#[OA\Post(
		path: "/api/{controller}/push",
		summary: "Обновить (если в теле передан ID) или создать новый элемент ОС (если ID не заполнен)",
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\MediaType(
				mediaType: "application/json",
				schema: new OA\Schema(ref: "#/components/schemas/{model}(write)")
			),
		),
		responses: [
			new OA\Response(
				response: 200,
				description: "OK (создано)",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				),
			),
			new OA\Response(
				response: 201,
				description: "OK (обновлено)",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				),
			),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
    /**
     * Создаёт или обновляет запись компьютера из тела POST-запроса (upsert).
     * Если в теле передан `id` — выполняет обновление через actionUpdate.
     * Иначе ищет существующий компьютер по имени (findByAnyName) и обновляет его,
     * или создаёт новую запись через actionCreate.
     *
     * POST body: поля модели Comps в формате JSON (в т.ч. опционально: id)
     *
     * Дополнительно (служба инвентаризации Windows):
     *  - `open_sessions` — полный список ключей сессий, открытых на этой ОС. Если передан
     *    (в т.ч. пустой), открытые в журнале входов сессии этой ОС, которых в списке нет,
     *    закрываются как потерянные ({@see LoginJournal::closeLost()});
     *  - запрос без данных инвентаризации (только id/name) — heartbeat: обновляется
     *    только updated_at ({@see pushHeartbeat()}).
     *
     * @return mixed
     * @throws BadRequestHttpException если тело запроса не удалось загрузить в модель
     */
    public function actionPush() {
    	/** @var Comps $loader */
		$loader = new $this->modelClass();
		$body = Yii::$app->getRequest()->getBodyParams();

		//грузим переданные данные
		if (!$loader->load($body,'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}

		//передали ID?
		$id = Yii::$app->request->getBodyParam('id');
		$comp = $id ? Comps::findOne($id) : null;
		$byName = strlen((string)$loader->name) ? Comps::findByAnyName($loader->name,'workgroup') : null;
		if (!is_object($comp) && is_object($byName) && $byName->id) $comp = $byName;

		if (is_object($comp) && $this->isHeartbeat($body, $comp, $byName)) {
			$result = $this->pushHeartbeat($comp);
		} elseif (is_object($comp)) {
			$result = $this->runAction('update',['id'=>$comp->id]);
		} else {
			$result = $this->runAction('create');
		}

		if (array_key_exists('open_sessions',$body) && is_array($body['open_sessions'])
			&& $result instanceof Comps && !$result->hasErrors() && $result->id
		) {
			LoginJournal::closeLost($result->id, array_filter($body['open_sessions'],'is_string'));
		}

		return $result;
	}

	/** поля тела запроса, которые не являются данными инвентаризации */
	const PUSH_SERVICE_FIELDS=['id','name','open_sessions'];

	/**
	 * Запрос — heartbeat: данных инвентаризации в нём нет, а имя (если передано) —
	 * имя этой же записи. Переименование ОС (id прежний, имя новое) — не heartbeat,
	 * оно идёт обычным обновлением.
	 */
	protected function isHeartbeat(array $body, Comps $comp, $byName): bool
	{
		if (count(array_diff(array_keys($body), static::PUSH_SERVICE_FIELDS))) return false;
		if (!array_key_exists('name',$body)) return true;
		return is_object($byName) && $byName->id == $comp->id;
	}

	/**
	 * Отметка «хост на связи»: только updated_at, без save(). Полная валидация и
	 * обработка связей на каждый heartbeat от каждого хоста — лишняя нагрузка, а история
	 * изменений это поле и так игнорирует (HistoryModel::$ignoreFieldChanges).
	 */
	protected function pushHeartbeat(Comps $comp): Comps
	{
		$comp->updateAttributes(['updated_at'=>gmdate('Y-m-d H:i:s')]);
		return $comp;
	}

	/**
	 * Переопределение testSearch для REST: помимо базового сценария поиска
	 * по атрибуту сгенерированной модели проверяет, что по известному имени
	 * из demo-дампа ('msk-esxi1') возвращается запись с name='MSK-ESXi1'.
	 * Раньше это покрывалось отдельным CompsCest::searchByName.
	 */
	public function testSearch(): array
	{
		$scenarios = parent::testSearch();
		$scenarios[] = [
			'name' => 'search by demo name',
			'method' => 'GET',
			'route' => '{controller}/search',
			'GET' => ['name' => 'msk-esxi1'],
			'response' => 200,
			'assert' => static function (\ApiTester $I) {
				$I->seeResponseIsJson();
				$I->seeResponseContainsJson(['name' => 'MSK-ESXi1']);
			},
		];
		return $scenarios;
	}

	/**
	 * Сценарии push службы инвентаризации Windows: heartbeat и список открытых сессий.
	 * Общий upsert (создание/обновление ОС данными инвентаризации) пока без провайдера.
	 */
	public function testPush(): array
	{
		/** @var Comps $comp */
		$comp = \app\generation\ModelFactory::create(Comps::class, []);
		$historyCount = static function () use ($comp): int {
			return (int)\app\models\CompsHistory::find()->where(['master_id' => $comp->id])->count();
		};
		$history = $historyCount();
		//отметка заведомо из прошлого, чтобы heartbeat было видно
		$comp->updateAttributes(['updated_at' => '2020-01-01 00:00:00']);

		$session = static function () use ($comp): LoginJournal {
			$rec = new LoginJournal();
			$rec->setAttributes([
				'session_uid' => sprintf('%08x-c0de-4000-8000-%012x', time(), mt_rand()),
				'comp_name' => 'rest-comp-push-test',
				'user_login' => 'DOMAIN\\rest_comp_push_tester',
				'comps_id' => $comp->id,
				'time' => time() - 300,
				'local_time' => time(),
			]);
			$rec->save();
			return $rec;
		};
		$kept = $session();
		$lost = $session();

		return [
			[
				'name' => 'push upsert',
				'skip' => true,
				'reason' => 'TODO: для upsert данными инвентаризации нужен отдельный провайдер (см. tests/rest-todo.md)',
			],
			[
				'name' => 'push heartbeat',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => ['id' => $comp->id],
				'response' => 200,
				'assert' => static function (\ApiTester $I) use ($comp, $history, $historyCount, $kept, $lost) {
					$I->seeResponseContainsJson(['id' => $comp->id]);
					$fresh = Comps::findOne($comp->id);
					Assert::assertGreaterThan('2026-01-01', $fresh->updated_at, 'heartbeat должен обновить updated_at');
					Assert::assertSame($history, $historyCount(), 'heartbeat не должен писать историю ОС');
					//списка открытых сессий в запросе нет — сессии не трогаем
					Assert::assertTrue(LoginJournal::findOne($kept->id)->isOpen);
					Assert::assertTrue(LoginJournal::findOne($lost->id)->isOpen);
				},
			],
			[
				'name' => 'push open sessions closes lost',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => ['id' => $comp->id, 'open_sessions' => [$kept->session_uid]],
				'response' => 200,
				'assert' => static function () use ($kept, $lost) {
					Assert::assertTrue(LoginJournal::findOne($kept->id)->isOpen);
					$closed = LoginJournal::findOne($lost->id);
					Assert::assertFalse($closed->isOpen);
					Assert::assertSame(LoginJournal::END_LOST, (int)$closed->end_type);
				},
			],
			[
				'name' => 'push empty open sessions closes all',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => ['id' => $comp->id, 'open_sessions' => []],
				'response' => 200,
				'assert' => static function () use ($kept) {
					Assert::assertFalse(LoginJournal::findOne($kept->id)->isOpen);
				},
			],
		];
	}
}
