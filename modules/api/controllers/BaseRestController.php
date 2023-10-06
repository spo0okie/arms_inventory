<?php

namespace app\modules\api\controllers;

/**
 * Базовый контроллер для REST API
 * Авторизация требуется на все операции по умолчанию
 *
 */


use app\models\Users;
use HttpInvalidParamException;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\filters\auth\HttpBasicAuth;
use yii\web\HttpException;
use yii\web\User;

class BaseRestController extends \yii\rest\ActiveController
{
	public $modelClass='app\models\ArmsModel';
	
	public $anyoneActions=[];
	public $viewActions=['index','view','search','filter','download'];
	public $editActions=['create','update','delete','upload'];
	public static $searchFields=[	//набор полей по которым можно делать серч с мапом в SQL поля
		'name'=>'name'
	];
	public static $searchFieldsLike=[];	//набор полей по которым можно делать Like поиск
	
	
	public static $searchOrder=[]; //порядок в котором сортировать поиск
	
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(\Yii::$app->params['useRBAC'])) {
			
			$behaviors['access']=[
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					['allow' => true, 'actions'=>$this->anyoneActions,	'roles'=>['?']],
					['allow' => true, 'actions'=>$this->viewActions,	'roles'=>['@']],
					['allow' => true, 'actions'=>$this->editActions,	'roles'=>['editor']],
				],
				'denyCallback' => function ($rule, $action) {
					throw new  \yii\web\ForbiddenHttpException('Access denied');
				}
			];
			
			$behaviors['authenticator'] = [
				'class' => HttpBasicAuth::class,
				'auth' => function ($login, $password) {
					/** @var $user Users */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) {
						return $user;
					}
					return null;
				},
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
		/** @var $search ActiveQuery */
		$search=$class::find();
		
		$filtersCount=0; //счетчик примененных фильтров
		foreach (static::$searchFields as $param=>$field) {
			$value=\Yii::$app->request->get($param);
			if (!is_null($value)) {
				$search->andWhere([$field=>$value]);
				$filtersCount++;
			}
		}
		
		foreach (static::$searchFieldsLike as $param=>$field) {
			$value=\Yii::$app->request->get($param);
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
