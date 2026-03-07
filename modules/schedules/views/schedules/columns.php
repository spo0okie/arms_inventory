<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $searchModel app\modules\schedules\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
return [
	[
		'attribute'=>'name',
		'format'=>'raw',
		'value'=>function($data) {
			return Html::a($data->name,['view','id'=>$data->id]);
		}
	],

	'description',
	'workTimeDescription',
];
