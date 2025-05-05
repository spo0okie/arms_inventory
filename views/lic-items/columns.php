<?php

/* @var $this yii\web\View */
$renderer=$this;
return [
	'lic_group_id',
	[
		'attribute'=>'descr',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return $renderer->render('/lic-items/item',['model'=>$item,'name'=>$item->descr]);
		}
	],
	'comment',
	'status'
];
