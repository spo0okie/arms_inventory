<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
	if (!isset($no_class)) {
		if (!isset($item_class)) {
			$item_class='segments-item '.$model->code;
		}
	} else {
		$item_class='segments-item';
	}
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'item_class'=>$item_class,
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'modal'=>true,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			//'nameSuffix'=>'',
			'noSpaces'=>true,
		]),
	]);
} else echo "Отсутствует";