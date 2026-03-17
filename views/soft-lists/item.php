<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

if (!empty($model)) {
	if (!isset($name)) $name=$model->descr;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'ttipUrl'=>Url::to(['soft-lists/ttip','id'=>$model->id]),
		'updateUrl'=>['soft-lists/update','id'=>$model->id,'return'=>'previous'],
	]);
}
