<?php

use app\components\ItemObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->name,
		'static'=>true,
		'item_class'=>'material-item cursor-default',
	]);
} else echo "Отсутствует";
