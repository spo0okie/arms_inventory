<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ExpandableCardWidget;
use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;
use app\models\Techs;
use app\models\TechsSearch;
use app\models\TechStates;
use app\models\TechTypes;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\web\JsExpression;

if (!isset($searchModel)) $searchModel=new TechsSearch();
if(!isset($static_view)) $static_view=false;

$renderer = $this;

if (
	!empty($searchModel->type_id)
	&&
	(is_object($type= TechTypes::findOne($searchModel->type_id)))
) {
	$comment=$type->comment_name;
} else {
	$comment=$searchModel->attributeLabels()['comment'];
}


$columns=[
	'attach'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/map/item-attachments', ['model' => $data]);
		}
	],

	'num'=> [
		'value' => function ($data) use ($renderer,$static_view) {
			return $renderer->render('/techs/item', ['model' => $data,'static_view'=>$static_view]);
		}
	],
	
	'sn',
	'inv_num',
	'uid',
	'inv_sn'=>[
		'value' => function ($data) use ($renderer) {
			$tokens=[];
			
			if (strlen($data->sn)) $tokens[]=$data->sn;
			if (strlen($data->inv_num)) $tokens[]=$data->inv_num;
			if (strlen($data->uid)) $tokens[]=$data->uid;
			return Html::encode(implode(', ',$tokens));
			
		},
		'contentOptions'=>function ($data) {
			/* @var $data Techs */
			return [
				'class'=>'inv_num_col',
				'qtip_ttip'=>
					'<strong>'.$data->getAttributeIndexLabel('sn').':</strong> '.($data->sn?$data->sn:'<i>отсутствует</i>').
					'<br />'.
					'<strong>'.$data->getAttributeIndexLabel('inv_num').':</strong> '.($data->inv_num?$data->inv_num:'<i>отсутствует</i>').
					($data->uid?('<br /><strong>'.$data->getAttributeIndexLabel('uid').':</strong> '.$data->uid):'')
			];
		},
	
	],
	
	'user' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->user)?$renderer->render('/users/item', ['model' => $data->user,'short'=>true]):null;
		}
	],
	
	'user_position' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->user) ?"<span class='arm_user_position'>{$data->user->Doljnost}</span>": null;
		},
	],
	
	'user_dep' => [
		'value' => function ($data) use ($renderer) {
			return (is_object($data->user) && is_object($data->user->orgStruct)) ?
				$renderer->render('/org-struct/item',['model'=>$data->user->orgStruct]):null;
		},
	],
	'departments_id' => [
		'value' => function ($data) {
			return (is_object($data->department)) ? $data->department->name:null;
		},
	],

	'partners_id' => [
		'value' => function ($data) use ($renderer) {
			return (is_object($data->partner)) ? $renderer->render('/partners/item', ['model' => $data->partner,'static_view'=>true]) :null;
		},
	],
	
	'comp_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->comp) ? $renderer->render('/comps/item', ['model' => $data->comp]) : null;
		},
		'contentOptions'=>function ($data) {return [
			'class'=>'arm_hostname '.$data->updatedRenderClass
		];}
	],
	
	'comp_hw' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/hwlist/shortlist',['model'=>$data->hwList,'arm_id'=>$data->id]);
		},
	],
	
	'ip' => [
		'value' => function ($data) use ($renderer) {
			if (is_object($data)) {
				$output=[];
				
				if (is_object($data->comp)) {
					foreach ($data->comp->netIps as $ip)
						$output[$ip->addr]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);
				}
				
				foreach ($data->netIps as $ip)
					$output[$ip->addr]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);
				
				return ExpandableCardWidget::widget(['content'=>implode('<br />',$output)]);
			}
			return null;
		},
	],
	'mac' => [
		'value'=>function ($data) {return Html::tag('span', Techs::formatMacs($data->mac,'<br />'),[
			'class'=>'mac_address'
		]);},
	],
	
	'model' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->model) ? $renderer->render('/tech-models/item', ['model' => $data->model, 'long' => true]) : null;
		}
	],
	'place' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/places/item', ['model' => $data->place, 'full' => true]);
		}
	],
	'services_ids' => [
		'value' => function ($data) use ($renderer) {
			return ListObjectsWidget::widget([
				'models'=>$data->services,
				'title'=>false,
				'item_options'=>['static_view'=>true],
				'card_options'=>['cardClass'=>'mb-0']
			]);
		},
	],
	
	'state_id' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/tech-states/item', ['model' => $data->state]);
		},
		'filterType'=>GridView::FILTER_SELECT2,
		'filter'=> TechStates::fetchNames(),
		'filterWidgetOptions' => [
			'hideSearch'=>true,
			'showToggleAll'=>false,
			'pluginOptions' => [
				'allowClear' => true,
				'placeholder' => '',
				//'debug' => true,
				'multiple'=>true,
				'closeOnSelect' =>false,
				'selectionAdapter' => new JsExpression('$.fn.select2.amd.require("CountChoicesSelectionAdapter")'),
			],
		],
	],
	'comment'=> [
		'label'=> $comment,
		'format' => 'ntext',
		//'value' => function ($data) use ($searchModel){return $data->comment.' '.$searchModel->model_id;}
	],
	'effectiveMaintenanceReqs' => [
		'value' => function ($data) {
			/** @var $data Techs */
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'effectiveMaintenanceReqs',
				'title'=>false,
				'item_options'=>[
					'static_view'=>true,
					'jobs'=>$data->maintenanceJobs
				],
			]);
		},
	],
	'maintenanceJobs' => [
		'value' => function ($data) {return ModelFieldWidget::widget(['model'=>$data,'field'=>'maintenanceJobs','title'=>false,'item_options'=>['static_view'=>true]]);},
	],
	'lics' => [
		'value'=>function ($data) use ($renderer) {
			/** @var Techs $data */
			$items=[];
			foreach ($data->licItems as $item) $items[]=$renderer->render('/lic-items/item',['model'=>$item]);
			foreach ($data->licGroups as $item) $items[]=$renderer->render('/lic-groups/item',['model'=>$item]);
			foreach ($data->licKeys as $item) $items[]=$renderer->render('/lic-keys/item',['model'=>$item]);
			return count($items)?ExpandableCardWidget::widget(['content'=>implode('<br>',$items),'cardClass'=>'line-br']):'';
		},
	],
	'itStaff' =>[
		'contentOptions' => ['item_options'=>['short'=>true]],
	]

];

if (Yii::$app->params['techs.hostname.enable']??false) {
	$columns['hostname']=[
		'attribute'=>'hostname'
	];
}

return $columns;