<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
$renderer=$this;
return [
	'networks_ids'=>[
		'value'=>function($data) use ($renderer){
			if (is_array($data->networks)) {
				$output=[];
				foreach ($data->networks as $network)
					$output[]=$renderer->render('/networks/item',['model'=>$network]);
				return implode('<br />',$output);
			}
			return '';
		},
		'contentOptions'=>[
			'class'=>'text-right'
		]
	],
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
		'contentOptions'=>[
			'class'=>'text-right'
		]
	],
	'vlan',
	'domain_id'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('/net-domains/item',['model'=>$data->netDomain]);
		},
		'contentOptions'=>[
			'class'=>'text-center'
		]
	],
	'comment',
];
