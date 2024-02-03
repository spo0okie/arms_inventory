<?php


/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\models\Materials;

$renderer=$this;
if (!isset($showTypes)) $showTypes=true; //показывать тип материалов в имени

return [
	'place'=>[
		'value' => function($data) use($renderer){
			return $renderer->render('/places/item',['model'=>$data->place,'full'=>true]);
		}
	],
	'model'=>[
		'value' => function($data) use($renderer,$showTypes){
			/** @var Materials $data */
			return $renderer->render('/materials/item',[
				'model'=>$data,
				'from'=>false,
				'name'=>$showTypes?null:$data->model
			]);
		}
	],
	'comment',
	'date',
	'rest',
	'count'=>[
		'label'=>'Поступило',
	]
];
