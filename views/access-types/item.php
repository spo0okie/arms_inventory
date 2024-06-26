<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

if (!isset($static_view)) $static_view=false;
if (!isset($show_delete)) $show_delete=false;
if (!isset($name)) $name=$model->sname;
if (!isset($suffix)) $suffix='';
if (!isset($class)) $class='';

if (!empty($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>$class,
		//'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'name'=>$name,
			'nameSuffix'=>$suffix,
			'model'=>$model,
			'noDelete'=>!$show_delete,
			'static'=>$static_view,
		]),
	]);
}



?>


