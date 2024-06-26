<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;
if (!isset($class)) $class='text-monospace';
if (!isset($no_class)) $no_class=false;


if (is_object($model)) {
	if (!$no_class) $class .= ' ' . $model->segmentCode;
	if (!isset($name)) $name = $model->sname;
	if ($icon) $name = '<span class="fas fa-network-wired small"></span>' . $name;
	
	
	echo ItemObjectWidget::widget([
		'model' => $model,
		'archived_class' => 'text-decoration-line-through',
		'link' => LinkObjectWidget::widget([
			'model' => $model,
			//'noDelete'=>true,
			'static' => $static_view,
			'name' => $name,
			'noSpaces' => true
		]),
	]);
	
}