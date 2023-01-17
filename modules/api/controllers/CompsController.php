<?php

namespace app\modules\api\controllers;

use app\models\CompsSearch;
use app\models\Users;
use yii\filters\auth\HttpBasicAuth;


class CompsController extends \yii\rest\ActiveController
{
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(\Yii::$app->params['useRBAC'])) {
			$behaviors['access']=[
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					['allow' => true, 'actions'=>['index','filter'], 'roles'=>['editor']],
					['allow' => true, 'actions'=>['create','view','update','search'], 'roles'=>['@','?']],
				],
				'denyCallback' => function ($rule, $action) {
					
						throw new  \yii\web\ForbiddenHttpException('Access denied');
				}
			];
			$behaviors['authenticator'] = [
				'class' => HttpBasicAuth::class,
				'only'=>['index','filter'],
				'auth' => function ($login, $password) {
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
	
	
	public $modelClass='app\models\Comps';
    
    public function actions()
    {
        $actions = parent::actions();
		//unset($actions['index']);
		$actions[]='search';
		$actions[]='filter';
        return $actions;
    }
	
	public function actionSearch($name,$domain=null,$ip=null){
		return \app\controllers\CompsController::searchModel($name,$domain,$ip);
	}
	
	public function actionFilter(){
		$searchModel = new CompsSearch();
		$searchModel->archived=\Yii::$app->request->get('showArchived',false);
		return $searchModel->search(\Yii::$app->request->queryParams)->models;
    }
}
