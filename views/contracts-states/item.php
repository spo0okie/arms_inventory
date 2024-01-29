<?php
/**
 * Элемент групп лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var ContractsStates $model */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\ContractsStates;

if (is_object($model)) {
	if (!isset($static_view)) $static_view=true;
	if (!isset($noDelete)) $noDelete=true;
	
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'noDelete'=>$noDelete,
			'hideUndeletable'=>$noDelete,
			'noSpaces'=>true,
		])
	]);
} else echo "Отсутствует";