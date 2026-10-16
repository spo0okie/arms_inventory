<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

if (!empty($model)) {
	if (!isset($name)) $name=$model->descr;
	echo ItemObjectWidget::widget([
		'show_archived'=>$show_archived??null, // Учитываем явный режим списка.
		'model'=>$model,
		'name'=>$name,
		'ttipUrl'=>Url::to(['soft-lists/ttip','id'=>$model->id]),
		'updateUrl'=>['soft-lists/update','id'=>$model->id,'return'=>'previous'],
	]);
}
