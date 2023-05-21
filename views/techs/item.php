<?php
/**
 * Элемент оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\Techs $model */
/* @var string $name */

use yii\helpers\Html;
if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
	
    if (!isset($name)) $name=$model->num;

	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=>\app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$name,
			'static'=>$static_view,
			'noDelete'=>true,
			'noSpaces'=>true,
		])
	]);
}