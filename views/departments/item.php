<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

if (!isset($static_view)) $static_view=true;

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'item_class'=>'segments-item',
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