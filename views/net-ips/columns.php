<?php

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
$renderer=$this;
return [
	'text_addr'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		}
	],
	'network'=>[
		'value'=>function($data) use ($renderer){
			return ModelWidget::widget(['model'=>$data->network]);
		}
	],
	'vlan'=>[
		'value'=>function($data) use ($renderer){
			if (is_object($data->network))
				return ModelWidget::widget(['model'=>$data->network->netVlan]);
			return null;
		}
	],
	'attached'=>[
		'value'=>function($data) use ($renderer){
			$objects=[];
			
			if (is_array($data->comps) && count($data->comps)) {
				foreach ($data->comps as $comp) $objects[]=ModelWidget::widget(['model'=>$comp,'options'=>['static_view'=>false]]);
			}
			if (is_array($data->techs) && count($data->techs)) {
				foreach ($data->techs as $tech) $objects[]=ModelWidget::widget(['model'=>$tech,'options'=>['static_view'=>false]]);
			}
			if (is_array($data->users) && count($data->users)) {
				foreach ($data->users as $user) $objects[]=ModelWidget::widget(['model'=>$user,'options'=>['short'=>true,'static_view'=>false,'noDelete'=>true]]);
			}
			
			if (count($objects)) return implode(', ',$objects);
			return null;
		}
	],
	'comment',
];


