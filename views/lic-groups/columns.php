<?php


/* @var $this yii\web\View */

use app\components\ExpandableCardWidget;
use app\components\gridColumns\ItemColumn;

$renderer=$this;
return [
	//'descr',
	'descr'=>['class'=> ItemColumn::class,],
	[
		'attribute'=>'itemsCount',
		'header'=>'Закупок<br/>акт/всего',
		'headerOptions'=>['qtip_ttip'=>'Активных (не истёкших) закупок / всего закупок этого типа лицензий'],
		'format'=>'raw',
		'value'=>function($item) {
			return $item->activeItemsCount.'/'.count($item->licItems);
		},
		'contentOptions'=>function($item){
			return [
				'class'=>count($item->licItems)?($item->activeItemsCount?'table-success':'table-danger'):'alert-gray-striped'
			];
		},
	],
	[
		'attribute'=>'keysCount',
		'header'=>'Лицензий<br/>исп/всего',
		'headerOptions'=>['qtip_ttip'=>'Распределённых (используемых) лицензий / всего активных лицензий этого типа'],
		'format'=>'raw',
		'value'=>function($item) {
			return $item->usedCount.'/'.$item->activeCount;
		},
		'contentOptions'=>function($item){
			return [
				'class'=>$item->usedCount==$item->activeCount?
					($item->activeCount?'table-success':'alert-gray-striped'):
					($item->usedCount>$item->activeCount?'table-danger':'table-info')
			];
		},
	],
	'comment',
	//'created_at',
	
	//['class' => 'yii\grid\ActionColumn'],
];