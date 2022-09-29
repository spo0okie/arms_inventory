<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */
/* @var $name string */

if (is_object($model)) {
	$name=$model->compName.' ('.$model->age.')';
	if (is_object($model->comp))
		echo $this->render('/comps/item',['model'=>$model->comp,'name'=>$name]);
	else
		echo $name;
}

