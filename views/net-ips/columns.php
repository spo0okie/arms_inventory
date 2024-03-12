<?php

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
			return $renderer->render('/networks/item',['model'=>$data->network]);
		}
	],
	'vlan'=>[
		'value'=>function($data) use ($renderer){
			if (is_object($data->network))
				return $renderer->render('/net-vlans/item',['model'=>$data->network->netVlan]);
			return null;
		}
	],
	'attached'=>[
		'value'=>function($data) use ($renderer){
			$objects=[];
			
			if (is_array($data->comps) && count($data->comps)) {
				foreach ($data->comps as $comp) $objects[]=$renderer->render('/comps/item',['model'=>$comp,'static_view'=>true]);
			}
			if (is_array($data->techs) && count($data->techs)) {
				foreach ($data->techs as $tech) $objects[]=$renderer->render('/techs/item',['model'=>$tech,'static_view'=>true]);
			}
			if (is_array($data->users) && count($data->users)) {
				foreach ($data->users as $user) $objects[]=$renderer->render('/users/item',['model'=>$user,'short'=>true,'static_view'=>true]);
			}
			
			if (count($objects)) return implode(', ',$objects);
			return null;
		}
	],
	'comment',
];