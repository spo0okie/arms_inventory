<?php

use app\components\ItemObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\ArmsModel */

if (!empty($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>$class??'',
		//'archived_class'=>'text-decoration-line-through',
		'name'=>$name??null,
		'nameSuffix'=>$suffix??'',
		'noDelete'=>!($show_delete??false),
		'static'=>$static_view??false,
		'modal'=>$modal??false,
	]);
}