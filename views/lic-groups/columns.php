<?php


/* @var $this yii\web\View */
$renderer=$this;
return [
	//'descr',
	[
		'attribute'=>'descr',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return $renderer->render('/lic-groups/item',['model'=>$item]);
		}
	],
	[
		'attribute'=>'itemsCount',
		'header'=>'Закупок<br/>акт/всего',
		'format'=>'raw',
		'value'=>function($item) {
			return $item->activeItemsCount.'/'.count($item->licItems);
		}
	],
	[
		'attribute'=>'keysCount',
		'header'=>'Ключей<br/>исп/всего',
		'format'=>'raw',
		'value'=>function($item) {
			return $item->usedCount.'/'.$item->activeCount;
		}
	],
	[
		'attribute'=>'comment',
		'format'=>'ntext'
	],
	//'created_at',
	
	//['class' => 'yii\grid\ActionColumn'],
];