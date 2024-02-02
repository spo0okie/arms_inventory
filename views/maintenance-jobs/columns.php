<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */


if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
	],
	'description'=>['format' =>'text'],
];