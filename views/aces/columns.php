<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ModelFieldWidget;

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	'subject_nodes'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'field'=>'nodes',
			'lineBr'=>false,
			'card_options'=>['cardClass' => 'p-1 text-nowrap',],
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
				'show_phone'=>$data->hasPhoneAccess(),
				'short'=>true,
			],
			'glue'=>$glue
		];}
	],
	'subjects'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'card_options'=>['cardClass' => 'p-1 text-nowrap',],
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>$data->hasIpAccess(),
				'show_phone'=>$data->hasPhoneAccess(),
				'short'=>true,
			],
			'glue'=>$glue
		];}
	],
	'access_types'=>[
		'value'=>function($data) use ($renderer,$glue){
			$items=[];
			foreach ($data->accessTypes as $type) {
				$params=$data->getIpParams()[$type->id]??null;
				$items[]=$type->renderItem($renderer,[
					'static_view'=>true,
					'suffix'=>$params?': '.$params:'',
				]);
			}
			return implode($glue,$items);
		},
	],
	'name'=>[
		'contentOptions'=>['static_view'=>false,'modal'=>true]
	],
	'schedule'=>[
		'value'=>function($data) use ($renderer){
			if (is_object($data->acl) && is_object($data->acl->schedule))
				return $renderer->render('/scheduled-access/item',['model'=>$data->acl->schedule,'static_view'=>false,'modal'=>true]);
			return '<i>отсутствует</i>';
		}
	],
	'resource'=>[
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data->acl))
				return $renderer->render('/acls/item',['model'=>$data->acl,'static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	'resource_nodes'=>[
		'contentOptions'=>function($data) use ($glue){ return [
			'model'=>$data->acl,
			'field'=>'nodes',
			'lineBr'=>false,
			'item_options'=>[
				'show_ips'=>is_object($data->acl)?$data->acl->hasIpAccess():false,
				'ips_prefix'=>':',
				'ips_glue'=>',',
				'ips_options'=>['static_view'=>true]
			],
			'glue'=>$glue,
		];}
	],

	//['class' => 'yii\grid\ActionColumn'],
];
