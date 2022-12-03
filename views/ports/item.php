<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

if (!isset($static_view)) $static_view=false;
if (!isset($include_tech)) $include_tech=false;
if (!isset($reverse)) $reverse=false;
if (empty($badge)) {
	$spanClass='';
	$hrefClass='';
} else {
	$spanClass='badge bg-primary text-white';
	$hrefClass='text-white';
}

if (!empty($modal)) {
	$options=['data-reload-page-on-submit'=>1];
	$hrefClass.=' open-in-modal-form';
} else {
	$options=[];
}

if (!empty($model)) {
	if ($include_tech && !$reverse) {
		if (!is_null($model->tech))
			echo $this->render('/techs/item', ['model'=>$model->tech,'static_view'=>true]).\app\models\Ports::$tech_postfix;
		
		if (!is_null($model->arm))
			echo $this->render('/arms/item', ['model'=>$model->arm,'static_view'=>true]).\app\models\Ports::$tech_postfix;
	}

	
	$url=$static_view?'/ports/view':'/ports/update';
	
	if (!isset($name)) $name=$model->name;
	
	$link=\app\components\LinkObjectWidget::widget([
		'name'=>\app\models\Ports::$port_prefix.$model->name,
		'model'=>$model,
		'url'=>[$url,'id'=>$model->id,'return'=>'previous'],
		'cssClass'=>$hrefClass,
		'static'=>true,
		'hrefOptions'=>$options
	]);
	
	if (empty($badge)) {
		echo $link;
	} else {
		echo "<span class='$spanClass'>$link</span>";
	}
	
	if ($include_tech && $reverse) {
		if (!is_null($model->tech))
			echo \app\models\Ports::$tech_postfix.' '.$this->render('/techs/item', ['model'=>$model->tech,'static_view'=>true]);
		
		if (!is_null($model->arm))
			echo \app\models\Ports::$tech_postfix.' '.$this->render('/arms/item', ['model'=>$model->arm,'static_view'=>true]);
	}
} ?>