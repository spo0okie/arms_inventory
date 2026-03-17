<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'item_class'=>'net-vlans-item text-monospace '.$model->domainCode,
		'ttipUrl'=>Url::to(['net-vlans/ttip','id'=>$model->id]),
		'updateUrl'=>['net-vlans/update','id'=>$model->id,'return'=>'previous'],
	]);
}
