<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\CompsSearch;
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
		path: "/web/api/{controller}/push",
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
     * @return mixed
     * @throws BadRequestHttpException если тело запроса не удалось загрузить в модель
     */
    public function actionPush() {
    	/** @var Comps $loader */
		$loader = new $this->modelClass();
	
		//грузим переданные данные
		if (!$loader->load(Yii::$app->getRequest()->getBodyParams(),'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}
		
		//передали ID?
		if ($loader->id) {
			return $this->runAction('update',['id'=>$loader->id]);
		}
		
		$search=Comps::findByAnyName($loader->name,'workgroup');
		if (is_object($search)&&$search->id) {
			return $this->runAction('update',['id'=>$search->id]);
		}

		return $this->runAction('create');
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
	 * TODO provider для actionPush — пока skip до проработки контракта upsert в тестах.
	 */
	public function testPush(): array
	{
		return [[
			'name' => 'push upsert',
			'skip' => true,
			'reason' => 'TODO: для actionPush нужен отдельный провайдер (см. tests/rest-todo.md)',
		]];
	}
}
