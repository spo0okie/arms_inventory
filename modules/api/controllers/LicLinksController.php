<?php

namespace app\modules\api\controllers;


use app\models\links\LicLinks;
use yii\web\NotFoundHttpException;

class LicLinksController extends BaseRestController
{

	public $modelClass='app\models\links\LicLinks';
	
	/** @noinspection PhpMethodNotFoundInspection */
	public function actionSearch(
		int $productId=null,
		string $objectType=null,
		string $licenseType=null,
		int $objId=null,
		string $objName=null,
		int $licId=null
	){
		//return $productId;
		
		if (!$objId && $objName) {
			$objClass=ucfirst($objectType);
			$objClass="app\\models\\$objClass";
			$obj=$objClass::findByAnyName($objName);
			if (!is_object($obj)) {
				throw new NotFoundHttpException("$objectType $objName not found");
			}
			$objId=$obj->id;
		}
		
		//var_dump($this->behaviors());
		
		return LicLinks::findProductLicenses(
			$productId,
			$objectType,
			$licenseType,
			$objId,
			$licId
		);
	}

}
