<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
$renderer=$this;
return [
	'networks_ids'=>[
		'value'=>function($data) use ($renderer){
			if (is_array($data->networks)) {
				$output=[];
				foreach ($data->networks as $network)
					$output[]=ModelWidget::widget(['model'=>$network]);
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
			return $renderer->render('//net-vlans/item',['model'=>$data]);
		},
		'contentOptions'=>[
			'class'=>'text-right'
		]
	],
	'vlan'=>['attribute'=>'vlan'],
	'domain_id'=>[
		'value'=>function($data) use ($renderer){
			return ModelWidget::widget(['model'=>$data->netDomain]);
		},
		'contentOptions'=>[
			'class'=>'text-center'
		]
	],
	'comment'=>[
		// Сохраняем штатный рендер текста и фильтр по исходному атрибуту.
		'contentOptions' => ['field' => 'commentRecursive'],
	],
];


