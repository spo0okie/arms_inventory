<?php


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	//фильтры по вычисляемым колонкам в AclsSearch не реализованы,
	//поэтому у всех колонок 'filter'=>false — чтобы строка фильтра не врала
	'subjects_nodes'=>[
		'filter'=>false,
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
		'filter'=>false,
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
		'filter'=>false,
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
		'filter'=>false,
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data) && is_object($data->schedule))
				return $data->schedule->renderItem($renderer,['static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	'resource'=>[
		'filter'=>false,
		'value'=>function($data) use ($renderer){
			if (is_object($data))
				return ModelWidget::widget(['model'=>$data,'options'=>['static_view'=>false,'modal'=>true]]);
			return '';
		}
	],
	'resource_nodes'=>[
		'filter'=>false,
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

