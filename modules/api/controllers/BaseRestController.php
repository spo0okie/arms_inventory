<?php

namespace app\modules\api\controllers;

/**
 * Базовый контроллер для REST API
 * Авторизация требуется на все операции по умолчанию
 *
 */


use app\models\Users;
use yii\filters\auth\HttpBasicAuth;
use yii\web\User;

class BaseRestController extends \yii\rest\ActiveController
{
 
	public $viewActions=['index','view','search'];
	public $editActions=['create','update','delete'];
	
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(\Yii::$app->params['useRBAC'])) {
			
			$behaviors['access']=[
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
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
	
}
