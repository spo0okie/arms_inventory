<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'item_class'=>'net-domains-item text-monospace net-domain-'.$model->name,
		'ttipUrl'=>Url::to(['net-domains/ttip','id'=>$model->id]),
		'updateUrl'=>['net-domains/update','id'=>$model->id,'return'=>'previous'],
	]);
}
