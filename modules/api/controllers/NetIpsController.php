<?php

namespace app\modules\api\controllers;

use app\models\Networks;
use app\models\Users;
use yii\filters\auth\HttpBasicAuth;
use yii\web\NotFoundHttpException;


class NetIpsController extends \yii\rest\ActiveController
{
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(\Yii::$app->params['useRBAC'])) {
			$behaviors['access']=[
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					['allow' => true, 'actions'=>['index'], 'roles'=>['editor']],
					['allow' => true, 'actions'=>['create','view','update','search','first-unused'], 'roles'=>['@','?']],
				],
				'denyCallback' => function ($rule, $action) {
					throw new  \yii\web\ForbiddenHttpException('Access denied');
				}
			];
			$behaviors['authenticator'] = [
				'class' => HttpBasicAuth::class,
				'only'=>['index'],
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
	
	
	public $modelClass='app\models\NetIps';
    
    public function actions()
    {
        $actions = parent::actions();
		$actions[]='search';
        return $actions;
    }
    
    public function actionSearch($addr,$name=null,$comment=null){
    	return \app\controllers\NetIpsController::searchModel($addr,$name,$comment);
    }
	
	public function actionFirstUnused($text_addr){
    	if (!is_object($network=Networks::find()->where(['text_addr'=>$text_addr])->one()))  {
			throw new NotFoundHttpException("Network $text_addr not found");
		}
    	/* @var \app\models\Networks $network */
		return $network->firstUnusedIp;
	}
}
