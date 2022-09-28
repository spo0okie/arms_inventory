<?php

/**
 * Это рендер списка типов материалов
 */


/* @var $this yii\web\View */

$renderer = $this;

return [
	'name' => [
		'value' => function ($data) {
			return \yii\helpers\Html::a($data->name,['materials-types/view','id'=>$data->id]);
		},
	
	],
	'comment' => [
		'value' => function ($data) {
			return \yii\helpers\Html::a($data->comment,['materials-types/view','id'=>$data->id]);
		},
	],
	'rest' => [
		'value' => 'rest',
		'contentOptions'=>[
			'class'=>'text-right',
		]
	],
	'units'
];