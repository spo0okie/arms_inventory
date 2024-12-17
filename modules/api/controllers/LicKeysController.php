<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\Users;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class LicKeysController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class LicKeysController extends BaseRestController
{

	public $modelClass='app\models\LicKeys';
	
	public function actionSearch($product_id=null,$comp_name=null,$user_login=null){
		//$modelClass = $this->modelClass;
		
		$licKeys=[];
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
				foreach ($comp->licKeys as $licKey)
					foreach ($licKey->licItem->licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licKeys[]=$licKey;
			} catch (NotFoundHttpException $e) {
				$errors[]=$e->getMessage();
			}
		}
		
		if ($user_login) {
			if (!is_object($user= Users::findByLogin($user_login)))
				$errors[]="User '$user_login' not found";
			else {
				foreach ($user->licKeys as $licKey)
					foreach ($licKey->licItem->licGroup->soft_ids as $soft_id)
						if ($soft_id == $product_id)
							$licKeys[]=$licKey;
			}
		}
		
		if (!is_object($comp) && !is_object($user))
			throw new NotFoundHttpException(implode(',',$errors));
		
		return $licKeys;
	}

}
