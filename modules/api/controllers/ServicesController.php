<?php

namespace app\modules\api\controllers;

use app\models\CompsSearch;
use app\models\Users;
use yii\filters\auth\HttpBasicAuth;


class ServicesController extends \yii\rest\ActiveController
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
	
	
	public $modelClass='app\models\Services';
    
    public function actions()
    {
        $actions = parent::actions();
		unset($actions['index']);
		unset($actions['update']);
		unset($actions['create']);
		//$actions[]='search';
		//$actions[]='filter';
        return $actions;
    }
}
