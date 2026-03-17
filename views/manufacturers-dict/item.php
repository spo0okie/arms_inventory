<?php
/**
 * Элемент словарь производителей
 * Created by PhpStorm.
 * User: spookie
 * Date: 10.11.2020
 * Time: 19:40
 */
use app\components\ItemObjectWidget;

/* @var $model \app\models\ManufacturersDict */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
	$targetUrl=$static_view?
		['/manufacturers-dict/view','id'=>$model->id]:
		['/manufacturers-dict/update','id'=>$model->id];
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->word,
		'static'=>$static_view,
		'url'=>$targetUrl,
		'ttipUrl'=>null,
		'confirmMessage'=>'Удалить этот вариант написания производителя?',
	]);
} else echo "Отсутствует";
