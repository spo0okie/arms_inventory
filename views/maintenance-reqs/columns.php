<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */




if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	//'id' /* ??? */,
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
	],
	'description'=>['format' =>'text'],
	/*'services'=>[
		'value'=>function($data) use ($renderer){
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'services',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0']
			]);
		},
	],*/
	//'spread_comps' /* ??? */,
	//'spread_techs' /* ??? */,
	//'links' /* ??? */,
	//'updated_at' /* ??? */,
	//'updated_by' /* ??? */,
		
];