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

class BaseRestController extends ActiveController
{
	const SEARCH_BY_ANY_NAME='@search-by-any';
	
	public $modelClass='app\models\ArmsModel';

	public static $searchFields=['name'=>'name'];	//набор полей по которым можно делать поиск с маппингом в SQL поля
	public static $searchFieldsLike=[];				//набор полей по которым можно делать Like поиск
	public static $searchJoin=[];					//что нужно join-ить для поиска
	public static $searchOrder=[]; 					//порядок в котором сортировать поиск
	
	/**
	 * Карта доступа с какими полномочиями что можно делать
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
			ArmsBaseController::PERM_ANONYMOUS=>[],
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
	
	
	public function actionSearch() {
		foreach (static::$searchFields as $param=>$field) {
			if ($field===static::SEARCH_BY_ANY_NAME && ($value= Yii::$app->request->get($param))) {
				$class=$this->modelClass;
				/** @var ArmsModel $class */
				return $class::findByAnyName($value);
			}
		}
		return $this->searchFilter()->one();
	}
	
	public function actionFilter() {
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
}
