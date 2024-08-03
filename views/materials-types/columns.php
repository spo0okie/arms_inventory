<?php

/**
 * Это рендер списка типов материалов
 */


/* @var $this yii\web\View */

use yii\helpers\Html;

$renderer = $this;

return [
	'name' => [
		'value' => function ($data) {
			return Html::a($data->name,['materials-types/view','id'=>$data->id]);
		},
	
	],
	'comment' => [
		'value' => function ($data) {
			return Html::a($data->comment,['materials-types/view','id'=>$data->id]);
		},
	],
	'count' => [
		'value' => 'count',
		'contentOptions'=>[
			'class'=>'text-right',
		]
	],
	'rest' => [
		'value' => 'rest',
		'contentOptions'=>[
			'class'=>'text-right',
		]
	],
	'units'
];