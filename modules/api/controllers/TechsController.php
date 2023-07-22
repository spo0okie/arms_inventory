<?php

namespace app\modules\api\controllers;



use app\models\Users;
use yii\filters\auth\HttpBasicAuth;
use yii\web\User;

class TechsController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Techs';
	
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		if (!empty(\Yii::$app->params['useRBAC'])) {
			$behaviors['access']=[
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					['allow' => true, 'actions'=>['index'], 'roles'=>['editor']],
					//['allow' => true, 'actions'=>['create','view','update','search'], 'roles'=>['@','?']],
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

	public function actionSearchByMac($mac){
		//ищем телефонный аппарат по номеру
		$tech = \app\models\Techs::find()
			->where(['mac' => $mac ])
			->one();
		//если нашли
		if (is_object($tech)){
			//он прикреплен к АРМ?
			return $tech;
		}
		throw new \yii\web\NotFoundHttpException("not found");
	}

	public function actionSearchByUser($id){
		//ищем пользователя
		$user = \app\models\Users::findOne($id);
		/**
		 * @var $user \app\models\Users
		 */
		//если нашли
		if (is_object($user)){
			//он прикреплен к АРМ?
			$arms=$user->techs;
			if (is_array($arms)) {
				//перебираем армы
				foreach ($arms as $arm){
					//ищем у них телефоны
					$phones=$arm->voipPhones;
					if (is_array($phones)) foreach ($phones as $phone) {
						if (strlen($phone->comment) && (int)$phone->comment) return $phone->comment;
					}
					if ($arm->isVoipPhone && strlen($arm->comment) && (int)$arm->comment) {
						return $arm->comment;
					}
				}
			}
		}
		throw new \yii\web\NotFoundHttpException("not found");
	}

}
