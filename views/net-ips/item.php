<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */



if (!isset($class)) $class='';
if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;
if (!isset($no_class)) $no_class=false;

if (!empty($model)) {
	if (!$no_class&&is_object($model->network)) $class.=' '.$model->network->segmentCode;
	if (!isset($name)) $name=$model->sname;
	if ($icon) $name='<span class="fas fa-network-wired small"></span>'.$name;
	
	
	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'link'=>\app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			'noSpaces'=>true
		]),
	]);
}