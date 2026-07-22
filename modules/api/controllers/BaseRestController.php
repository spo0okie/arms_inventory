<?php

namespace app\modules\api\controllers;

/**
 * Базовый контроллер для REST API
 * Авторизация требуется на все операции по умолчанию
 *
 */


use app\controllers\ArmsBaseController;
use app\generation\ModelFactory;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\Users;
use Yii;
use yii\base\UnknownPropertyException;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use OpenApi\Attributes as OA;
use yii\web\Response;

/**
 * @method array actions() - для получения стандартных действий ActiveController (index, view, create, update, delete)
 * @OA\Tag(
 *   name="{controller}",
 *   description="{model->titles}"
 * )
 */
class BaseRestController extends ActiveController
{
	const SEARCH_BY_ANY_NAME='@search-by-any';
	
	public $modelClass= 'app\models\base\ArmsModel';

	public static array $searchFields=['name'=>'name'];		//набор полей по которым можно делать поиск с маппингом в атрибуты модели
	public static array $searchFieldsLike=[];				//набор полей по которым можно делать Like поиск
	public static array $searchOrder=[]; 					//порядок в котором сортировать поиск
	
	/**
	 * Действия которые отключены в контроллере (для блокировки в потомках)
	 * @return array
	 */
	public function disabledActions(): array
	{
		return [];
	}
	
	/**
	 * Проверяем доступность $action в этом контроллере
	 * @param $action
	 * @return void
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function checkDisabledActions($action): void
	{
		if (in_array($action, $this->disabledActions(), true)) {
			throw new \yii\web\ForbiddenHttpException("Action $action is disabled.");
		}
	}
	
	/**
	 * Карта доступа с какими полномочиями, что можно делать
	 * @return array
	 */
	public function accessMap(): array
	{
		$class=StringHelper::class2Id($this->modelClass);
		return [
			'edit'=>['create','update','delete','upload'],			//редактирование всего
			'view'=>['index','view','search','filter','download'],	//чтение всего
			"view-$class"=>['view','search','download'],			//чтение объектов этого класса по одному
			"index-$class"=>['index','filter'],						//чтение объектов этого класса  списком
			"update-$class"=>['create','update','upload'],			//обновление объектов этого класса
			"delete-$class"=>['delete'],							//удаление объектов этого класса
			ArmsBaseController::PERM_ANONYMOUS=>['preflight'],		//проверка разрешений CORS (делается до авторизации)
			ArmsBaseController::PERM_AUTHENTICATED=>[],
		];
	}
	
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(Yii::$app->params['useRBAC'])) {
			$behaviors['access']=ArmsBaseController::buildAccessRules($this->accessMap());
			$behaviors['authenticator'] = [
				'class' => HttpBasicAuth::class,
				'auth' => function ($login, $password) {
					/** @var Users $user */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
				'except' => $this->accessMap()[ArmsBaseController::PERM_ANONYMOUS],	//отключаем авторизацию для действий доступных без нее
			];
		}
		$behaviors['contentNegotiator'] = [
			'class' => ContentNegotiator::class,
			'formats' => [
				'application/json' => Response::FORMAT_JSON,
				'application/xml'  => Response::FORMAT_XML,
				'text/plain'       => Response::FORMAT_RAW,
				'*/*'              => Response::FORMAT_JSON,
			],
		];
		
		return $behaviors;
	}
	
	/**
	 * Строит поисковый запрос исходя из полей которые переданы в запросе
	 * @return ActiveQuery
	 * @throws BadRequestHttpException
	 * @throws UnknownPropertyException
	 */
	public function searchFilter(): ActiveQuery
	{

		/** @var \app\models\base\ArmsModel $class */
		$class=$this->modelClass;
		$model=new $class();
		$search=$class::find();

		$filtersCount=0; //счетчик примененных фильтров
		foreach (static::$searchFields as $param=>$attr) {
			if (is_numeric($param)) {$param=$attr;}		//на случай если объявили ['id','name'] без маппинга
			$value= Yii::$app->request->get($param);	//проверяем запрашивали фильтр по параметру
			if (!is_null($value)) {
				$filtersCount++;

				//поисковый параметр задан, поэтому нам нужно подтянуть джойны и отфильтровать по нужному полю
				$search->joinWith($model->getAttributeJoins($attr));

				if (in_array($param,static::$searchFieldsLike))		//если этот параметр предназначен для неточной фильтрации
					$search->andWhere(['like',$model->getAttributeFilter($attr),$value]);	//ищем через like
				else												//иначе
					$search->andWhere([$model->getAttributeFilter($attr)=>$value]);			//ищем строго
			}
		}
		
		if (!$filtersCount) { //не удалось применить ни одного фильтра
			throw new BadRequestHttpException('Empty search filter');
		}
		
		if (count(static::$searchOrder)) {	//если задана сортировка - сортируем
			$search->orderBy(static::$searchOrder);
		}
		
		return $search;
	}
	
	
	#[OA\Get(
		path: "/web/api/{controller}/search",
		summary: "Поиск одного объекта по набору полей.",
		parameters: [
			new OA\Parameter(
				name: "{searchFields}",
				description: "Фильтр по атрибутам модели",
				
				in: "query",
				required: false,
			)
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(
						ref: "#/components/schemas/{model}(read)"
					)
				)
			),
			new OA\Response(response: 404, description: "Ничего не найдено по запросу")
		]
	)]
	/**
	 * Возвращает единственный объект модели, найденный по query-параметрам фильтрации.
	 * Поддерживает специальный режим поиска по любому имени (SEARCH_BY_ANY_NAME).
	 * Если ни одного объекта не найдено — выбрасывает 404.
	 *
	 * GET-параметры: поля из static::$searchFields контроллера-наследника
	 *
	 * @return ActiveRecord|null
	 * @throws BadRequestHttpException если не передан ни один параметр фильтрации
	 * @throws NotFoundHttpException если объект не найден
	 */
	public function actionSearch(): ActiveRecord|null {
		$this->checkDisabledActions('search');
		foreach (static::$searchFields as $param=>$field) {
			if ($field===static::SEARCH_BY_ANY_NAME && ($value= Yii::$app->request->get($param))) {
				$class=$this->modelClass;
				/** @var \app\models\base\ArmsModel $class */
				return $class::findByAnyName($value);
			}
		}
		$result=$this->searchFilter()->one();
		if (!$result) throw new NotFoundHttpException('Nothing found');
		return $result;
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/filter",
		summary: "Поиск нескольких объектов по набору полей.",
		parameters: [
			new OA\Parameter(
				name: "{searchFields}",
				description: "Фильтр по атрибутам модели",
				in: "query",
				required: false,
			)
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(
						type: "array",
						items: new OA\Items(
							ref: "#/components/schemas/{model}(read)"
						)
					)
				),
			),
			new OA\Response(response: 404, description: "Ничего не найдено по запросу"),
		]
	)]
	/**
	 * Возвращает список объектов модели, отфильтрованный по query-параметрам.
	 * Результат оборачивается в ActiveDataProvider для поддержки пагинации.
	 *
	 * GET-параметры: поля из static::$searchFields контроллера-наследника
	 *
	 * @return BaseDataProvider
	 * @throws BadRequestHttpException если не передан ни один параметр фильтрации
	 */
	public function actionFilter(): BaseDataProvider
	{
		$this->checkDisabledActions('filter');
		return new ActiveDataProvider(['query' => $this->searchFilter()]);
	}
	
	/**
	 * Обрабатывает CORS preflight-запрос (OPTIONS).
	 * Устанавливает HTTP-заголовки для разрешения кросс-доменных запросов:
	 * Access-Control-Allow-Methods и Access-Control-Allow-Headers.
	 * Действие доступно без авторизации (PERM_ANONYMOUS).
	 *
	 * @see https://www.yiiframework.com/wiki/175/how-to-create-a-rest-api
	 */
	public function actionPreflight() {
		$content_type = 'application/json';
		$status = 200;
		$message = 'OK';
		
		// set the status
		$status_header = 'HTTP/1.1 ' . $status . ' ' . $message;
		header($status_header);
		
		//header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
		header("Access-Control-Allow-Headers: Authorization");
		header('Content-type: ' . $content_type);
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/",
		summary: "Список всех элементов",
		parameters: [
			new OA\Parameter(name: "{expand}"),
			new OA\Parameter(name: "{pagination}"),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(
						type: "array",
						items: new OA\Items(
							ref: "#/components/schemas/{model}(read)"
						)
					)
				),
			),
		]
	)]
	/**
	 * Возвращает постраничный список всех объектов модели.
	 * Делегирует выполнение стандартному action 'index' ActiveController.
	 * Поддерживает параметры expand и pagination.
	 *
	 * GET-параметры: expand (список связей через запятую), page, per-page
	 *
	 * @return void
	 */
	public function actionIndex()
	{
		$this->checkDisabledActions('index');
		$this->actions()['index']->run();
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/{id}",
		summary: "Прочитать элемент по ID",
		parameters: [
			new OA\Parameter(
				name: "id",
				description: "ID элемента",
				in: "path",
				required: true,
				schema: new OA\Schema(type: "integer")
			),
			new OA\Parameter(name: "{expand}"),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(
						ref: "#/components/schemas/{model}(read)"
					)
				)			),
			new OA\Response(response: 404, description: "Элемент с таким ID не найден"),
		]
	)]
	/**
	 * Возвращает один объект модели по его первичному ключу.
	 * Делегирует выполнение стандартному action 'view' ActiveController.
	 * Поддерживает параметр expand для включения связанных данных.
	 *
	 * GET-параметры:
	 * @param mixed $id  Первичный ключ записи
	 *
	 * @return void
	 */
	public function actionView($id)
	{
		$this->checkDisabledActions('view');
		$this->actions()['view']->run($id);
	}
	
	#[OA\Post(
		path: "/web/api/{controller}/",
		summary: "Создать новый элемент",
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\MediaType(
				mediaType: "application/json",
				schema: new OA\Schema(ref: "#/components/schemas/{model}(write)")
			),
		),
		responses: [
			new OA\Response(
				response: 201,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				),
			),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
	/**
	 * Создаёт новый объект модели из тела запроса.
	 * Делегирует выполнение стандартному action 'create' ActiveController.
	 * При успехе возвращает созданный объект с кодом 201.
	 * При ошибке валидации возвращает 422 с описанием ошибок.
	 *
	 * POST/PUT body: поля модели в формате JSON
	 *
	 * @return void
	 */
	public function actionCreate()
	{
		$this->checkDisabledActions('create');
		$this->actions()['create']->run();
	}
	
	#[OA\Put(
		path: "/web/api/{controller}/{id}",
		summary: "Обновить элемент с указанным ID",
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\MediaType(
				mediaType: "application/json",
				schema: new OA\Schema(ref: "#/components/schemas/{model}(write)")
			),
		),
		parameters: [new OA\Parameter(
			name: "id",
			description: "ID элемента",
			in: "path",
			required: true,
			schema: new OA\Schema(type: "integer")
		)],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				),
			),
			new OA\Response(response: 404, description: "Элемент с таким ID не найден"),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
	/**
	 * Обновляет существующий объект модели по его первичному ключу.
	 * Делегирует выполнение стандартному action 'update' ActiveController.
	 * При успехе возвращает обновлённый объект с кодом 200.
	 * При ошибке валидации возвращает 422.
	 *
	 * GET-параметры:
	 * @param mixed $id  Первичный ключ записи
	 *
	 * PUT body: поля модели в формате JSON
	 *
	 * @return void
	 */
	public function actionUpdate($id)
	{
		$this->checkDisabledActions('update');
		$this->actions()['update']->run($id);
	}
	
	#[OA\Delete(
		path: "/web/api/{controller}/{id}",
		summary: "Удалить элемент с указанным ID",
		responses: [
			new OA\Response(response: 204, description: "OK"),
			new OA\Response(response: 404, description: "Элемент с таким ID не найден"),
		]
	)]
	/**
	 * Удаляет объект модели по его первичному ключу.
	 * Делегирует выполнение стандартному action 'delete' ActiveController.
	 * При успехе возвращает код 204 (No Content).
	 *
	 * GET-параметры:
	 * @param mixed $id  Первичный ключ записи
	 *
	 * @return void
	 */
	public function actionDelete($id)
	{
		$this->checkDisabledActions('delete');
		$this->actions()['delete']->run($id);
	}

	/**
	 * Кеш тестовых данных (моделей/пейлоадов) по имени класса модели.
	 * Используется {@see getTestData()} и провайдерами testXxx() для REST acceptance.
	 *
	 * @var array<string, array<string, \app\models\base\ArmsModel>>
	 */
	protected static array $restTestDataCache = [];

	/**
	 * Готовит набор моделей и пейлоадов для REST acceptance-сценариев
	 * (см. tests/rest/RestAccessCest.php). Каждый ключ предназначен под отдельный сценарий,
	 * чтобы тесты не мешали друг другу (view/update/delete работают на разных id).
	 *
	 *  - `view`        — сохранённая модель; её id используется для GET /controller/{id};
	 *  - `update`      — сохранённая модель; её id используется для PUT /controller/{id};
	 *  - `delete`      — сохранённая модель; её id используется для DELETE /controller/{id};
	 *  - `create-data` — сгенерированная, НЕ сохранённая модель; её атрибуты идут в тело POST /controller;
	 *  - `update-data` — сгенерированная, НЕ сохранённая модель; её атрибуты идут в тело PUT /controller/{id}.
	 *
	 * Кешируется по modelClass, чтобы многократные вызовы в рамках одного suite
	 * не создавали дубли.
	 *
	 * @return array<string, ArmsModel>
	 */
	public function getTestData(): array
	{
		$class = $this->modelClass;
		if (empty(self::$restTestDataCache[$class])) {
			self::$restTestDataCache[$class] = [
				'view'        => ModelFactory::create($class, []),
				'update'      => ModelFactory::create($class, []),
				'delete'      => ModelFactory::create($class, []),
				'create-data' => ModelFactory::create($class, ['save' => false]),
				'update-data' => ModelFactory::create($class, ['save' => false]),
			];
		}
		return self::$restTestDataCache[$class];
	}

	/**
	 * Возвращает список отключённых testXxx() — по умолчанию совпадает с disabledActions().
	 * Переопределяется в наследнике, если action нужно оставить рабочим, но пропустить тест.
	 *
	 * @return array
	 */
	public function disabledTests(): array
	{
		return $this->disabledActions();
	}

	/**
	 * Возвращает пейлоад скрытого skip-сценария (единственный элемент массива).
	 */
	protected static function skipScenario(string $name, string $reason): array
	{
		return [['name' => $name, 'skip' => true, 'reason' => $reason]];
	}

	/**
	 * Безопасно извлекает первое значение поля $searchFields из готовой модели —
	 * используется провайдерами search/filter для формирования query-параметра.
	 */
	protected function pickSearchParam(ArmsModel $model): ?array
	{
		foreach (static::$searchFields as $param => $attr) {
			if (is_numeric($param)) $param = $attr;
			// Атрибут поиска должен быть реальным столбцом таблицы. Если это только
			// виртуальный getter/alias — search/filter в actionSearch/actionFilter
			// упадут SQL-ошибкой (Unknown column), что для acceptance нерелевантно.
			if (!$model->hasAttribute($attr)) continue;
			$value = $model->$attr;
			if ($value === null || $value === '' || is_array($value)) continue;
			return [$param => (string)$value];
		}
		return null;
	}

	/**
	 * Базовый testIndex: GET /{controller} → 200.
	 * Предварительно создаёт набор данных, чтобы список не был пустым.
	 */
	public function testIndex(): array
	{
		$this->getTestData();
		return [[
			'name' => 'list',
			'method' => 'GET',
			'route' => '{controller}',
			'response' => 200,
		]];
	}

	/**
	 * Базовый testView: GET /{controller}/{id} → 200 на существующей модели.
	 */
	public function testView(): array
	{
		$data = $this->getTestData();
		return [[
			'name' => 'view existing',
			'method' => 'GET',
			'route' => '{controller}/' . $data['view']->id,
			'response' => 200,
		]];
	}

	/**
	 * Базовый testCreate: POST /{controller} с валидным пейлоадом → 201.
	 * Полезные PATCH-расширения (альтернативные пейлоады, 422 на невалид) — в наследниках.
	 */
	public function testCreate(): array
	{
		$data = $this->getTestData();
		return [[
			'name' => 'create valid',
			'method' => 'POST',
			'route' => '{controller}',
			'body' => $this->bodyAttributes($data['create-data']),
			'response' => [200, 201],
		]];
	}

	/**
	 * Базовый testUpdate: PUT /{controller}/{id} с валидным пейлоадом → 200.
	 */
	public function testUpdate(): array
	{
		$data = $this->getTestData();
		return [[
			'name' => 'update existing',
			'method' => 'PUT',
			'route' => '{controller}/' . $data['update']->id,
			'body' => $this->bodyAttributes($data['update-data']),
			'response' => [200, 204],
		]];
	}

	/**
	 * Атрибуты модели для тела REST create/update БЕЗ первичного ключа.
	 * PK не входит в пейлоад: при create его назначает БД, при update он берётся из URL.
	 * Иначе присланный в теле id несохранённой модели (=null) обнуляет первичный ключ
	 * на load() и роняет save() (см. Users::afterSave -> absorbUser -> null id).
	 *
	 * @param \app\models\base\ArmsModel $model
	 * @return array
	 */
	protected function bodyAttributes(\app\models\base\ArmsModel $model): array
	{
		return array_diff_key($model->attributes, array_flip($model::primaryKey()));
	}

	/**
	 * Базовый testDelete: DELETE /{controller}/{id} → 204.
	 */
	public function testDelete(): array
	{
		$data = $this->getTestData();
		return [[
			'name' => 'delete existing',
			'method' => 'DELETE',
			'route' => '{controller}/' . $data['delete']->id,
			'response' => [200, 204],
		]];
	}

	/**
	 * Базовый testSearch: GET /{controller}/search?field=value.
	 * Если у контроллера нет $searchFields — пропускаем.
	 * Допустимые коды: 200 (нашли), 404 (не нашли) — оба валидны для смоук-проверки action.
	 */
	public function testSearch(): array
	{
		if (empty(static::$searchFields)) {
			return static::skipScenario('no search fields', 'static::$searchFields пуст');
		}
		$data = $this->getTestData();
		$param = $this->pickSearchParam($data['view']);
		if ($param === null) {
			return static::skipScenario('no usable search value', 'атрибуты searchFields пусты у тестовой модели');
		}
		return [[
			'name' => 'search by ' . array_key_first($param),
			'method' => 'GET',
			'route' => '{controller}/search',
			'GET' => $param,
			'response' => [200, 404],
		]];
	}

	/**
	 * Базовый testFilter: GET /{controller}/filter?field=value.
	 * 400 допускается для контроллеров, где filter требует обязательных полей.
	 */
	public function testFilter(): array
	{
		if (empty(static::$searchFields)) {
			return static::skipScenario('no search fields', 'static::$searchFields пуст');
		}
		$data = $this->getTestData();
		$param = $this->pickSearchParam($data['view']);
		if ($param === null) {
			return static::skipScenario('no usable search value', 'атрибуты searchFields пусты у тестовой модели');
		}
		return [[
			'name' => 'filter by ' . array_key_first($param),
			'method' => 'GET',
			'route' => '{controller}/filter',
			'GET' => $param,
			'response' => [200, 400],
		]];
	}

	/**
	 * Базовый testPreflight: OPTIONS /{controller}/index → 200 (CORS preflight).
	 */
	public function testPreflight(): array
	{
		return [[
			'name' => 'cors preflight',
			'method' => 'OPTIONS',
			'route' => '{controller}/index',
			'response' => 200,
		]];
	}
}
