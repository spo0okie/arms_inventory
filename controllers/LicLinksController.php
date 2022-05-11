<?php


namespace app\controllers;


use kartik\grid\EditableColumnAction;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class LicLinksController extends \yii\web\Controller
{
	
	public function linkActions() {
		$actions=[];
		foreach (\app\models\links\LicLinks::$licTypes as $licType)
			foreach (\app\models\links\LicLinks::$objTypes as $objType) {
				$class='\\app\\models\\links\\'.\app\models\links\LicLinks::linksClassName($licType,$objType);
				$actions[\app\models\links\LicLinks::linksCtrlName($licType,$objType)]=[
					'class' => EditableColumnAction::className(),		// action class name
					'modelClass' => $class,	// the update model class
					/*
					'outputValue' => function ($model, $attribute, $key, $index) {
						//$fmt = Yii::$app->formatter;
						$value = $model->$attribute;                 // your attribute value
						return $value;
						//if ($attribute === 'buy_amount') {           // selective validation by attribute
						//	return $fmt->asDecimal($value, 2);       // return formatted value if desired
						//} elseif ($attribute === 'publish_date') {   // selective validation by attribute
						//	return $fmt->asDate($value, 'php:Y-m-d');// return formatted value if desired
						//}
						//return '';                                   // empty is same as $value
					},
					'outputMessage' => function($model, $attribute, $key, $index) {
						return '';                                  // any custom error after model save
					},
					// 'showModelErrors' => true,                     // show model errors after save
					// 'errorOptions' => ['header' => '']             // error summary HTML options
					// 'postOnly' => true,
					// 'ajaxOnly' => true,
					// 'findModel' => function($id, $action) {},
					// 'checkAccess' => function($action, $model) {}
					*/
				];
			}
		return $actions;
	}
	
	public function actions()
	{
		return ArrayHelper::merge(parent::actions(), $this->linkActions());
	}
}