<?php

namespace app\modules\api\controllers;

use app\models\LicKeys;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Class LicKeysController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class LicKeysController extends BaseRestController
{

	public $modelClass='app\models\LicKeys';
	
	public function actionSearch($product_id=null,$comp_name=null,$user_login=null): ActiveRecord{
		return LicLinksController::filterQuery(
			LicKeys::find(),
			'keys',
			$product_id,
			$user_login,
			$comp_name
		)->one();
	}
	
	public function actionFilter($product_id=null,$comp_name=null,$user_login=null): ActiveDataProvider{
		return new ActiveDataProvider(['query' => LicLinksController::filterQuery(
			LicKeys::find(),
			'keys',
			$product_id,
			$user_login,
			$comp_name
		)]);
	}

}
