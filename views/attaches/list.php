<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\widgets\page\ModelWidget;

/** @var yii\web\View $this */
/** @var app\models\Attaches[] $models */

if (!isset($static_view)) $static_view = false;
if (!isset($glue)) $glue='<br />';

$items=[];
foreach ($models as $model)
	$items[]=ModelWidget::widget(['model'=>$model]);
	echo implode($glue,$items);
