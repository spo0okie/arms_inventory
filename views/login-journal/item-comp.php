<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */
/* @var $name string */

if (is_object($model)) {
	$name=$model->compName.' ('.$model->age.')';
	if (is_object($model->comp))
		echo ModelWidget::widget(['model'=>$model->comp,'options'=>['name'=>$name]]);
	else
		echo $name;
}



