<?php


/* @var $this yii\web\View */

use app\components\ExpandableCardWidget;
use app\components\gridColumns\ItemColumn;

$renderer=$this;

//прогрев кэшей: счетчики закупок/ключей на каждую строку иначе грузят свои связи отдельно
\app\models\LicItems::cacheAllItems();
\app\models\LicKeys::cacheAllItems();

return [
	//'descr',
	'descr'=>['class'=> ItemColumn::class,],
	[
		'attribute'=>'itemsCount',
		'header'=>'Закупок<br/>акт/всего',
		'headerOptions'=>['qtip_ttip'=>'Активных (не истёкших) закупок / всего закупок этого типа лицензий'],
		'format'=>'raw',
		'value'=>function($item) {
			return $item->activeItemsCount.'/'.count($item->licItemsCached);
		},
		'contentOptions'=>function($item){
			return [
				'class'=>count($item->licItemsCached)?($item->activeItemsCount?'table-success':'table-danger'):'alert-gray-striped'
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