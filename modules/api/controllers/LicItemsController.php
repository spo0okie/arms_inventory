<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use yii\web\NotFoundHttpException;

/**
 * Class LicKeysController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class LicItemsController extends BaseRestController
{

	public $modelClass='app\models\LicItems';

	public function actionSearch($product_id=null,$comp_name=null){
		/** @var Comps $comp */
		$comp= Comps::find()->where(['name' => strtoupper($comp_name)])->one();
		if ($comp === null)
			throw new NotFoundHttpException("Comp with name '$comp_name' not found");

		$arm=$comp->arm;
		if ($arm === null)
			throw new NotFoundHttpException("Comp with name '$comp_name' not attached to any ARM");

		foreach ($arm->licItems as $item) {
			foreach ($item->licGroup->soft_ids as $soft_id) if ($soft_id == $product_id) {
				return $item;
			}
		}

		throw new NotFoundHttpException("ARM with comp_name '$comp_name' got no license keys for product #$product_id");
	}

}
