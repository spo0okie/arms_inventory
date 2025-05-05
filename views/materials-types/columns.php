<?php

/**
 * Это рендер списка типов материалов
 */


/* @var $this yii\web\View */

use app\components\TextFieldWidget;
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
			return TextFieldWidget::widget(['model'=>$data,'field'=>'comment']);
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