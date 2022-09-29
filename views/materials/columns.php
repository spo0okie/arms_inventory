<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer=$this;
return [
	[
		'attribute'=>'place',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $renderer->render('/places/item',['model'=>$data->place,'full'=>true]);
		}
	],
	[
		'attribute'=>'model',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $renderer->render('/materials/item',['model'=>$data,'from'=>false]);
		}
	],
	'comment',
	'date',
	'rest'
];
