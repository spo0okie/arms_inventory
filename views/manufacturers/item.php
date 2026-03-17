<?php
/**
 * Элемент производитель
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $model \app\models\Manufacturers */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->name,
		'static'=>$static_view,
		'ttipUrl'=>Url::to(['/manufacturers/ttip','id'=>$model->id]),
	]);
} else echo "Отсутствует";
