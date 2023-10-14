<?php

namespace app\modules\api\controllers;

/**
 * Базовый контроллер для REST API
 * Авторизация требуется на все операции по умолчанию
 *
 */


use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\Users;
use HttpInvalidParamException;
use Yii;
use yii\db\ActiveQuery;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;

class BaseRestController extends ActiveController
{
	//как в карте доступов обозначать анонимный и авторизованный
	public static $PERM_ANONYMOUS='@anonymous';
	public static $PERM_AUTHENTICATED='@authorized';
	
	public $modelClass='app\models\ArmsModel';

	public static $searchFields=['name'=>'name'];	//набор полей по которым можно делать поиск с маппингом в SQL поля
	public static $searchFieldsLike=[];				//набор полей по которым можно делать Like поиск
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
			self::$PERM_ANONYMOUS=>[],
			self::$PERM_AUTHENTICATED=>[],
		];
	}
	
	/** @noinspection PhpUnusedParameterInspection */
	public static function buildAccessRules($map) {
		$rules=[];
		foreach ($map as $permission=>$actions) {
			$rule=['allow'=>true, 'actions'=>$actions];
			switch ($permission) {
				case self::$PERM_AUTHENTICATED:
					$rule['roles']=['@'];
					break;
				case self::$PERM_ANONYMOUS:
					$rule['roles']=['?'];
					break;
				default:
					$rule['permissions']=[$permission];
			}
			$rules[]=$rule;
		}
		return [
			'class' => AccessControl::class,
			'rules' => $rules,
			'denyCallback' => function ($rule, $action) {
				throw new  ForbiddenHttpException('Access denied');
			}
		];
	}
	
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(Yii::$app->params['useRBAC'])) {
			$behaviors['access']=static::buildAccessRules($this->accessMap());
			$behaviors['authenticator'] = [
				'class' => HttpBasicAuth::class,
				'auth' => function ($login, $password) {
					/** @var $user Users */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
				'except' => $this->accessMap()[static::$PERM_ANONYMOUS],	//отключаем авторизацию для действий доступных без нее
			];
		}
		return $behaviors;
	}
	
	/**
	 * Строит поисковый запрос исходя из полей которые переданы в запросе
	 * @return ActiveQuery
	 * @throws HttpInvalidParamException
	 */
	public function searchFilter() {
		$class=$this->modelClass;
		/** @var ArmsModel $class */
		$search=$class::find();
		/** @var $search ActiveQuery */
		
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
			throw new HttpInvalidParamException('Empty search filter');
		}
		
		if (count(static::$searchOrder)) {
			$search->orderBy(static::$searchOrder);
		}
		
		return $search;
	}
	
	public function actionSearch() {
		return $this->searchFilter()->one();
	}
	
	public function actionFilter() {
		return $this->searchFilter()->all();
	}
	
}
