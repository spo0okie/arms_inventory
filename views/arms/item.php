<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */

if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;

if (is_object($model)) {
	$ttipUrl=(!isset($no_ttip)||!$no_ttip)?Url::to(['/arms/ttip','id'=>$model->id]):null;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->num,
		'namePrefix'=>$icon?'<span class="fas fa-desktop"></span>':'',
		'static'=>$static_view,
		'ttipUrl'=>$ttipUrl,
		'updateUrl'=>['/arms/update','id'=>$model->id,'return'=>'previous'],
	]);
} else echo "Отсутствует";
