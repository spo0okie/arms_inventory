<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */


use app\components\ExpandableCardWidget;

if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
	],
	'description'=>['format' =>'text'],
	'schedule'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('/schedules/item',['model'=>$data->schedule]);
		},
	],
	'objects'=>[
		'value'=>function($data) use ($renderer){
			$output=[];
			foreach ($data->comps as $comp) {
				$output[]=$renderer->render('/comps/item',['model'=>$comp,'static_view'=>false]);
			}
			foreach ($data->techs as $tech) {
				$output[]=$renderer->render('/techs/item',['model'=>$tech,'static_view'=>false]);
			}
			foreach ($data->services as $service) {
				$output[]=$renderer->render('/services/item',['model'=>$service,'static_view'=>false]);
			}
			return ExpandableCardWidget::widget([
				'content'=>implode('<br />',$output)
			]);
		},
	],
];