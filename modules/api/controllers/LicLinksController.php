<?php

namespace app\modules\api\controllers;


use app\models\links\LicLinks;

class LicLinksController extends BaseRestController
{

	public $modelClass='app\models\links\LicLinks';

	public function actionSearch(
		int $productId=null,
		string $objectType=null,
		string $licenseType=null,
		int $objId=null,
		int $licId=null
	){
		//return $productId;
		return LicLinks::findProductLicenses(
			$productId,
			$objectType,
			$licenseType,
			$objId,
			$licId
		);
	}

}
