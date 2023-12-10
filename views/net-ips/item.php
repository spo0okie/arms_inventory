<?php

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */


use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

if (!isset($class)) $class='';
if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;
if (!isset($no_class)) $no_class=false;

if (!empty($model)) {
	if (!$no_class&&is_object($model->network)) $class.=' '.$model->network->segmentCode;
	if (!isset($name)) $name=$model->sname;
	if ($icon) $name='<span class="fas fa-network-wired small"></span>'.$name;
	
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			'noSpaces'=>true
		]),
	]);
}
