<?php


/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\models\Materials;

use app\components\widgets\page\ModelWidget;
$renderer=$this;
if (!isset($showTypes)) $showTypes=true; //показывать тип материалов в имени

return [
	'place'=>[
		'value' => function($data) use($renderer){
			return ModelWidget::widget(['model'=>$data->place,'options'=>['full'=>true]]);
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


