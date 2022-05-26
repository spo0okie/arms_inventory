<?php

namespace app\modules\api\controllers;


use yii\web\User;

class LicLinksController extends \yii\rest\ActiveController
{

	public $modelClass='app\models\links\LicLinks';

	public function actions()
	{
		return ['search'];
	}

	public function actionSearch(
		int $productId=null,
		string $objectType=null,
		string $licenseType=null,
		int $objId=null,
		int $licId=null
	){
		//return $productId;
		return \app\models\links\LicLinks::findProductLicenses(
			$productId,
			$objectType,
			$licenseType,
			$objId,
			$licId
		);
	}

}
