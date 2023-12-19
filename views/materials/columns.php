<?php


/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer=$this;
return [
	'place'=>[
		'value' => function($data) use($renderer){
			return $renderer->render('/places/item',['model'=>$data->place,'full'=>true]);
		}
	],
	'model'=>[
		'value' => function($data) use($renderer){
			return $renderer->render('/materials/item',['model'=>$data,'from'=>false]);
		}
	],
	'comment',
	'date',
	'rest',
	'count'=>[
		'label'=>'Поступило',
	]
];
