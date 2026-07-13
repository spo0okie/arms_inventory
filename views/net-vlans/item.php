<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	//VLAN раскрашивается маркером своего L2-домена (легаси CSS-класс по имени — fallback)
	$marker=$model->netDomain->marker??false;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'marker'=>$marker,
		'item_class'=>'net-vlans-item text-monospace '.($marker?'':$model->domainCode),
		'ttipUrl'=>Url::to(['net-vlans/ttip','id'=>$model->id]),
		'updateUrl'=>['net-vlans/update','id'=>$model->id,'return'=>'previous'],
	]);
}
