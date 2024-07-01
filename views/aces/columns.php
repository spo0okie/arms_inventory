<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ModelFieldWidget;

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	'subject_nodes'=>[
		'value'=>function($data) use ($glue){
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'nodes',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>[
					'static_view'=>true,
					'show_ips'=>$data->hasIpAccess(),
					'show_phone'=>$data->hasPhoneAccess(),
					'short'=>true,
				],
				'glue'=>'<br>'
			]);
		}
	],
	'subjects'=>[
		'value'=>function($data) use ($glue) {
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'subjects',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>[
					'static_view'=>true,
					'show_ips'=>$data->hasIpAccess(),
					'show_phone'=>$data->hasPhoneAccess(),
					'short'=>true,
				],
				'glue'=>'<br>'
			]);
		}
	],
	'access_types'=>[
		'value'=>function($data) use ($renderer,$glue){
			$items=[];
			foreach ($data->accessTypes as $type) {
				$params=$data->getIpParams()[$type->id]??null;
				$items[]=$renderer->render('/access-types/item',[
					'model'=>$type,
					'static_view'=>true,
					'suffix'=>$params?': '.$params:'',
				]);
			}
			return implode($glue,$items);
		}
	],
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('/aces/item',['model'=>$data,'static_view'=>false,'modal'=>true]);
		}
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
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data->acl)) return ModelFieldWidget::widget([
				'model'=>$data->acl,
				'field'=>'nodes',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>[
					'static_view'=>true,
					'show_ips'=>$data->acl->hasIpAccess(),
					'ips_prefix'=>':',
					'ips_glue'=>',',
					'ips_options'=>['static_view'=>true]
				],
				'glue'=>$glue,
			]);
			return '';
		}
	],

	//['class' => 'yii\grid\ActionColumn'],
];
