<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($static_view)) $static_view=false;
if (!isset($show_delete)) $show_delete=false;


if (!empty($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'noDelete'=>!$show_delete,
			'static'=>$static_view,
		]),
	]);
}