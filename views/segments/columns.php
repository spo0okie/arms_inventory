<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */


use yii\helpers\Html;


if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	//'id' /* ??? */,
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
	],
	'description'=>['format' =>'text'],
	'services_count'=>[
		'value'=>function($data) {
			return Html::a(count($data->services),['segments/view','id'=>$data->id,'tab'=>'services']);
		}
	],
	'networks_count'=>[
		'value'=>function($data) {
			return Html::a(count($data->networks),['segments/view','id'=>$data->id,'tab'=>'networks']);
		}
	],
	//'code' /* ??? */,
	//'history'=>['format' =>'text'],
	//'archived' /* ??? */,
	//'links' /* ??? */,
		
];