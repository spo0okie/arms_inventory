<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Aces;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($static_view)) $static_view=false;
if (!isset($modal)) $modal=false;
if (!isset($show_delete)) $show_delete=false;
if (!isset($name)) $name=$model->sname;
if (!isset($class)) $class='';

if (!empty($model)) {
	if ($name== Aces::$NAME_MISSING) $class.=' fst-italic';
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>$class,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'name'=>$name,
			'model'=>$model,
			'noDelete'=>!$show_delete,
			'static'=>$static_view,
			'modal'=>$modal
		]),
	]);
}