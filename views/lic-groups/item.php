<?php
/**
 * Элемент групп лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicGroups $model */

use yii\helpers\Html;
if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
	if (!isset($noDelete)) $noDelete=true;
	
	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=>\app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'noDelete'=>$noDelete,
			'hideUndeletable'=>$noDelete,
			'noSpaces'=>true,
		])
	]);
} else echo "Отсутствует";