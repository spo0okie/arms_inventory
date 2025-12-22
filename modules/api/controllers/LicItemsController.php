<?php

namespace app\modules\api\controllers;

use app\models\LicItems;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Class LicKeysController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class LicItemsController extends BaseRestController
{

	public $modelClass='app\models\LicItems';
	
	public function actionSearch($product_id=null,$comp_name=null,$user_login=null): ActiveRecord{
		return LicLinksController::filterQuery(
			LicItems::find(),
			'items',
			$product_id,
			$user_login,
			$comp_name
		)->one();
	}
	public function actionFilter($product_id=null,$comp_name=null,$user_login=null): ActiveDataProvider{
		return new ActiveDataProvider(['query' => LicLinksController::filterQuery(
			LicItems::find(),
			'items',
			$product_id,
			$user_login,
			$comp_name
		)]);
	}
}
