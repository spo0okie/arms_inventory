<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\Users;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class LicGroupsController extends BaseRestController
{

	public $modelClass='app\models\LicGroups';

	public function actionSearch($product_id=null,$comp_name=null,$user_login=null){
		//$modelClass = $this->modelClass;
		
		$licGroups=[];
		$errors=[];
		
		$comp=null;
		$user=null;
		/**
		 * @var $comp Comps
		 * @var $user Users
		 */
		
		if (!$product_id) {
			throw new BadRequestHttpException("No product_id passed");
		}

		if (!$comp_name && !$user_login) {
			throw new BadRequestHttpException("No login or computer name passed");
		}
		
		if ($comp_name) {
			try {
				$comp=\app\controllers\CompsController::searchModel($comp_name);
				foreach ($comp->licGroups as $licGroup)
					foreach ($licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licGroups[]=$licGroup;
			} catch (NotFoundHttpException $e) {
				$errors[]=$e->getMessage();
			}
		}
		
		if ($user_login) {
			if (!is_object($user= Users::findByLogin($user_login)))
				$errors[]="User '$user_login' not found";
			else {
				foreach ($user->licGroups as $licGroup)
					foreach ($licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licGroups[]=$licGroup;
			}
		}
		
		if (!is_object($comp) && !is_object($user))
			throw new NotFoundHttpException(implode(',',$errors));

		return $licGroups;
	}

}
