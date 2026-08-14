<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\widgets\page\ModelWidget;

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],

	//колонки-агрегаты (субъекты доступа) собираются из ACE этого ACL - models=>$data->aces;
	//остальные колонки - вычисляемые атрибуты самого ACL (ресурс, его узлы, типы доступа)
	'subjects_nodes'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'models'=>$data->aces,
			'field'=>'nodes',
			'card_options'=>['cardClass' => 'p-1 text-nowrap',],
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
				'show_phone'=>$data->hasPhoneAccess(),
				'short'=>true,
			],
			'glue'=>$glue
		];}
	],
	'subjects'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'models'=>$data->aces,
			'field'=>'subjects',
			'card_options'=>['cardClass' => 'p-1 text-nowrap',],
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
				'show_phone'=>$data->hasPhoneAccess(),
				'short'=>true,
			],
			'glue'=>$glue
		];}
	],
	'access_types'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'field'=>'accessTypes',
			'card_options'=>['cardClass' => 'p-1 text-nowrap',],
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
			],
			'glue'=>$glue
		];}
	],
	'schedule'=>[
		'value'=>function($data) use ($renderer){
			if (is_object($data->schedule))
				return $data->schedule->renderItem($renderer,['static_view'=>false,'modal'=>true]);
			return '<i>отсутствует</i>';
		}
	],
	'resource'=>[
		//ссылка ведет на сам ACL (его имя - имя ресурса), как и в списке ACE
		'value'=>function($data) use ($renderer){
			if (is_object($data))
				return ModelWidget::widget(['model'=>$data,'options'=>['static_view'=>false,'modal'=>true]]);
			return '';
		}
	],
	'resource_nodes'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'field'=>'nodes',
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
				'ips_prefix'=>':',
				'ips_glue'=>',',
				'ips_options'=>['static_view'=>true]
			],
			'glue'=>$glue,
		];}
	],

	//['class' => 'yii\grid\ActionColumn'],
];
