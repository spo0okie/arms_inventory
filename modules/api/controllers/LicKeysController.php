<?php

namespace app\modules\api\controllers;


class LicKeysController extends \yii\rest\ActiveController
{

	public $modelClass='app\models\LicKeys';

	public function actions()
	{
		return ['view'];
	}

	public function actionView($product_id,$comp_name){
		$modelClass = $this->modelClass;
		$comp=\app\models\Comps::find()->where(['name' => strtoupper($comp_name)])->one();
		if ($comp === null)
			throw new \yii\web\NotFoundHttpException("Comp with name '$comp_name' not found");

		$arm=$comp->arm;
		if ($arm === null)
			throw new \yii\web\NotFoundHttpException("Comp with name '$comp_name' not attached to any ARM");

		foreach ($arm->licKeys as $key) {
			foreach ($key->licItem->licGroup->soft_ids as $soft_id) if ($soft_id == $product_id) {
				return $key;
			}
		}

		throw new \yii\web\NotFoundHttpException("ARM with comp_name '$comp_name' got no license keys for product #$product_id");
	}

}
