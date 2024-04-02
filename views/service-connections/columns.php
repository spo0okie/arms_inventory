<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */



if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	'initiator'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/service-connections/part',['model'=>$data,'source'=>'initiator']);
		},
	],
	'initiator_service'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/services/item',['model'=>$data->initiator,'static_view'=>true]);
		},
		'contentOptions'=>[
			'class'=>'text-wrap'
		]
	],
	'initiator_nodes'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/service-connections/part',['model'=>$data,'source'=>'initiator','service'=>false,'details'=>false]);
		},
	],
	'initiator_details'=>[
		'value'=>function($data){
			return $data->initiator_details??'';
		},
	],
	'comment'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('/service-connections/item',['model'=>$data,'name'=>\Yii::$app->getFormatter()->asNtext($data->comment)]);
		},
		'contentOptions'=>[
			'class'=>'text-wrap'
		]
	],
	'target_service'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/services/item',['model'=>$data->target,'static_view'=>true]);
		},
		'contentOptions'=>[
			'class'=>'text-wrap'
		]
	],
	'target_nodes'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/service-connections/part',['model'=>$data,'source'=>'target','service'=>false,'details'=>false]);
		},
	],
	'target_details'=>[
		'value'=>function($data){
			return $data->target_details??'';
		},
	],
	'target'=>[
		'value'=>function($data) use ($renderer){
			return $this->render('/service-connections/part',['model'=>$data,'source'=>'target']);
		},
	],
	
];