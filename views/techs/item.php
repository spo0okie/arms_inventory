<?php
/**
 * Элемент оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var Techs $model */
/* @var string $name */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Techs;
if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
	
    if (!isset($name)) $name=$model->name;

	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$name,
			'static'=>$static_view,
			'noDelete'=>true,
			'noSpaces'=>true,
		])
	]);
}