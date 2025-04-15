<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$renderer=$this;
$manufacturers=\app\models\Manufacturers::fetchNames();
return [
	'descr'=> [
		'value'=>function($data) use ($renderer){
			return $renderer->render('/soft/item',[
				'model'=>$data,
				'name'=>(is_object($data->manufacturer)?$data->manufacturer->name.' ':'').$data->descr
			]);
		}
	],
	'comment',
	'hitsCount',
	'compsCount',
	'licGroupsCount',
];
