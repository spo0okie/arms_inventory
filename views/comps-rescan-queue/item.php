<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=true;

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model->soft,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model->soft,
			'modal'=>true,
			'noDelete'=>true,
			'static'=>$static_view,
		]).'// '.$model->updated_by.' ('.$model->updated_at.')',
	]);

} else echo "ERR";