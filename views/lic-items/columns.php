<?php

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
$renderer=$this;

//прогрев: колонка status считает usages через ключи закупки - без кэша ключи грузятся на каждую строку
\app\models\LicKeys::cacheAllItems();

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


