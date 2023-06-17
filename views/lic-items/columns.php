<?php

/* @var $this yii\web\View */
$renderer=$this;
return [
	[
		'attribute'=>'lic_group_id',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return $renderer->render('/lic-groups/item',['model'=>$item->licGroup]);
		}
	],
	[
		'attribute'=>'descr',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return $renderer->render('/lic-items/item',['model'=>$item,'name'=>$item->descr]);
		}
	],
	[
		'attribute'=>'comment',
		'format'=>'ntext'
	],
	'status'
];
