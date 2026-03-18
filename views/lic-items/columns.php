<?php

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
$renderer=$this;
return [
	'lic_group_id',
	[
		'attribute'=>'descr',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return ModelWidget::widget(['model'=>$item,'options'=>['name'=>$item->descr]]);
		}
	],
	'comment',
	'status'
];


