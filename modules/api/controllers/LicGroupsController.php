<?php

namespace app\modules\api\controllers;


use yii\web\User;

class LicGroupsController extends BaseRestController
{

	public $modelClass='app\models\LicGroups';
	public $anyoneActions=['search'];

	public function actions()
	{
		return array_merge(['search'],parent::actions());
	}

	public function actionSearch($product_id,$comp_name=null,$user_login=null){
		//$modelClass = $this->modelClass;
		
		$licGroups=[];
		$errors=[];
		
		$comp=null;
		$user=null;
		/**
		 * @var $comp \app\models\Comps
		 * @var $user \app\models\Users
		 */
		
		if (!$comp_name && !$user_login) {
			throw new \yii\web\BadRequestHttpException("No login or computer name passed");
		}
		
		if ($comp_name) {
			try {
				$comp=\app\controllers\CompsController::searchModel($comp_name);
				foreach ($comp->licGroups as $licGroup)
					foreach ($licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licGroups[]=$licGroup;
			} catch (\HttpException $e) {
				$errors[]=$e->getMessage();
			}
		}
		
		if ($user_login) {
			if (!is_object($user=\app\models\Users::findByLogin($user_login)))
				$errors[]="User '$user_login' not found";
			else {
				foreach ($user->licGroups as $licGroup)
					foreach ($licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licGroups[]=$licGroup;
			}
		}
		
		if (!is_object($comp) && !is_object($user))
			throw new \yii\web\NotFoundHttpException(implode(',',$errors));

		return $licGroups;
	}

}
