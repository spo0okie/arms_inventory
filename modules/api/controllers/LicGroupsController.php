<?php

namespace app\modules\api\controllers;

use app\models\LicGroups;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

class LicGroupsController extends BaseRestController
{

	public $modelClass='app\models\LicGroups';
	
	public function actionSearch($product_id=null,$comp_name=null,$user_login=null): ActiveRecord{
		return LicLinksController::filterQuery(
			LicGroups::find(),
			'group',
			$product_id,
			$user_login,
			$comp_name
		)->one();
	}
	
	public function actionFilter($product_id=null,$comp_name=null,$user_login=null): ActiveDataProvider{
		return new ActiveDataProvider(['query' => LicLinksController::filterQuery(
			LicGroups::find(),
			'group',
			$product_id,
			$user_login,
			$comp_name
		)]);
	}
}
