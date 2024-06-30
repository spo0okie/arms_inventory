<?php


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ModelFieldWidget;

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	'subjects_nodes'=>[
		'value'=>function($data) use ($glue,$renderer) {
			if (is_object($data)) return ModelFieldWidget::widget([
				'models'=>$data->aces,
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
			return '';
		}
	],
	'subjects'=>[
		'value'=>function($data) use ($glue,$renderer) {
			if (is_object($data)) return ModelFieldWidget::widget([
				'models'=>$data->aces,
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
			return '';
		}
	],
	'access_types'=>[
		'value'=>function($data) use ($glue,$renderer) {
			if (is_object($data)) return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'accessTypes',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>[
					'static_view'=>true,
					'show_ips'=>$data->hasIpAccess(),
				],
				'glue'=>'<br>'
			]);
			return '';
		}
	],
	'schedule'=>[
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data) && is_object($data->schedule))
				return $renderer->render('/scheduled-access/item',['model'=>$data->schedule,'static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	'resource'=>[
		'value'=>function($data) use ($renderer){
			if (is_object($data))
				return $renderer->render('/acls/item',['model'=>$data,'static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	'resource_nodes'=>[
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data)) return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'nodes',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>[
					'static_view'=>true,
					'show_ips'=>$data->hasIpAccess(),
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
