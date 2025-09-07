<?php

namespace app\modules\api\controllers;

/**
 * Базовый контроллер для REST API
 * Авторизация требуется на все операции по умолчанию
 *
 */


use app\controllers\ArmsBaseController;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\Users;
use Yii;
use yii\db\ActiveQuery;
use yii\filters\auth\HttpBasicAuth;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

use OpenApi\Attributes as OA;

/**
 * @OA\Tag(
 *   name="{controller}",
 *   description="{model->titles}"
 * )
 */
class BaseRestController extends ActiveController
{
	const SEARCH_BY_ANY_NAME='@search-by-any';
	
	public $modelClass='app\models\ArmsModel';

	public static $searchFields=['name'=>'name'];	//набор полей по которым можно делать поиск с маппингом в SQL поля
	public static $searchFieldsLike=[];				//набор полей по которым можно делать Like поиск
	public static $searchJoin=[];					//что нужно join-ить для поиска
	public static $searchOrder=[]; 					//порядок в котором сортировать поиск
	
	/**
	 * Действия которые отключены в контроллере (для блокировки в потомках)
	 * @return array
	 */
	public function disabledActions()
	{
		return [];
	}
	
	/**
	 * Проверяем доступность $action в этом контроллере
	 * @param $action
	 * @return void
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function checkDisabledActions($action)
	{
		if (in_array($action, $this->disabledActions(), true)) {
			throw new \yii\web\ForbiddenHttpException("Action $action is disabled.");
		}
	}
	
	/**
	 * Карта доступа с какими полномочиями, что можно делать
	 * @return array
	 */
	public function accessMap() {
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
					/** @var $user Users */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
				'except' => $this->accessMap()[ArmsBaseController::PERM_ANONYMOUS],	//отключаем авторизацию для действий доступных без нее
			];
		}
		return $behaviors;
	}
	
	/**
	 * Строит поисковый запрос исходя из полей которые переданы в запросе
	 * @return ActiveQuery
	 * @throws BadRequestHttpException
	 */
	public function searchFilter() {
		$class=$this->modelClass;
		/** @var ArmsModel $class */
		$search=$class::find();
		/** @var $search ActiveQuery */
		
		foreach (static::$searchJoin as $field) {
			$search->joinWith($field);
		}
		
		
		$filtersCount=0; //счетчик примененных фильтров
		foreach (static::$searchFields as $param=>$field) {
			$value= Yii::$app->request->get($param);
			if (!is_null($value)) {
				$search->andWhere([$field=>$value]);
				$filtersCount++;
			}
		}
		
		foreach (static::$searchFieldsLike as $param=>$field) {
			$value= Yii::$app->request->get($param);
			if (!is_null($value)) {
				$search->andWhere(['Like',$field,$value]);
				$filtersCount++;
			}
		}
		
		if (!$filtersCount) { //не удалось применить ни одного фильтра
			throw new BadRequestHttpException('Empty search filter');
		}
		
		if (count(static::$searchOrder)) {
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
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 404, description: "Ничего не найдено")
		]
	)]
	public function actionSearch() {
		$this->checkDisabledActions('search');
		foreach (static::$searchFields as $param=>$field) {
			if ($field===static::SEARCH_BY_ANY_NAME && ($value= Yii::$app->request->get($param))) {
				$class=$this->modelClass;
				/** @var ArmsModel $class */
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
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 404, description: "Ничего не найдено"),
		]
	)]
	public function actionFilter() {
		$this->checkDisabledActions('filter');
		return $this->searchFilter()->all();
	}
	
	/**
	 * CORS support
	 * https://www.yiiframework.com/wiki/175/how-to-create-a-rest-api
	 *
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
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
		]
	)]
	public function actionIndex()
	{
		$this->checkDisabledActions('index');
		$this->actions()['index']->run();
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/{id}",
		summary: "Прочитать элемент по ID",
		parameters: [new OA\Parameter(
			name: "id",
			description: "ID элемента",
			in: "path",
			required: true,
			schema: new OA\Schema(type: "integer")
		)],
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 404, description: "Элемент ID не найден"),
		]
	)]
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
				schema: new OA\Schema(ref: "#/components/schemas/{model}")
			),
		),
		responses: [
			new OA\Response(response: 201, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
	public function actionCreate()
	{
		$this->checkDisabledActions('create');
		$this->actions()['create']->run();
	}
	
	#[OA\Put(
		path: "/web/api/{controller}/{id}",
		summary: "Обновить элемент с указанным ID",
		parameters: [new OA\Parameter(
			name: "id",
			description: "ID элемента",
			in: "path",
			required: true,
			schema: new OA\Schema(type: "integer")
		)],
		responses: [
			new OA\Response(response: 200, description: "OK"),
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 404, description: "Элемент ID не найден"),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
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
			new OA\Response(response: 403, description: "Доступ запрещен"),
			new OA\Response(response: 404, description: "Элемент ID не найден"),
		]
	)]
	public function actionDelete($id)
	{
		$this->checkDisabledActions('delete');
		$this->actions()['delete']->run($id);
	}
}
