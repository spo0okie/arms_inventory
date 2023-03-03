<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\web\JsExpression;


$renderer = $this;

if (
	!empty($searchModel->type_id)
	&&
	(is_object($type=\app\models\TechTypes::findOne($searchModel->type_id)))
) {
	$comment=$type->comment_name;
} else {
	$comment=$searchModel->attributeLabels()['comment'];
}


return [
	'attach'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/item-attachments', ['model' => $data]);
		}
	],

	'num'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/item', ['model' => $data]);
		}
	],
	
	'sn'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/sn', ['model' => $data]);
		}
	],
	'inv_num'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/sn', ['model' => $data]);
		}
	],
	'inv_sn'=>[
		'value' => function ($data) use ($renderer) {
			$tokens=[];
			
			if (strlen($data->sn)) $tokens[]=$data->sn;
			if (strlen($data->inv_num)) $tokens[]=$data->inv_num;
			return \yii\helpers\Html::encode(implode(', ',$tokens));
			
		},
		'contentOptions'=>function ($data) {
			/* @var $data \app\models\OldArms */
			return [
				'class'=>'inv_num_col',
				'qtip_ttip'=>
					'<strong>'.$data->getAttributeIndexLabel('sn').':</strong> '.($data->sn?$data->sn:'<i>отсутствует</i>').
					'<br />'.
					'<strong>'.$data->getAttributeIndexLabel('inv_num').':</strong> '.($data->inv_num?$data->inv_num:'<i>отсутствует</i>')
			];
		},
	
	],
	
	'user' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->user)?$renderer->render('/users/item', ['model' => $data->user]):null;
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
				
				return implode(' ',$output);
			}
			return null;
		},
	],
	'mac' => [
		'value'=>function ($data) {return Html::tag('span',\app\models\Techs::formatMacs($data->mac),[
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
	
	'state_id' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/tech-states/item', ['model' => $data->state]);
		},
		'filterType'=>GridView::FILTER_SELECT2,
		'filter'=>\app\models\TechStates::fetchNames(),
		'filterWidgetOptions' => [
			'hideSearch'=>true,
			'showToggleAll'=>false,
			'pluginOptions' => [
				'allowClear' => true,
				'placeholder' => '',
				//'debug' => true,
				'multiple'=>true,
				'closeOnSelect' =>false,
				'selectionAdapter' => new JsExpression('$.fn.select2.amd.require("ArmsSelectionAdapter")'),
			],
		],
	],
	'comment'=> [
		'label'=> $comment,
		'format' => 'ntext',
		//'value' => function ($data) use ($searchModel){return $data->comment.' '.$searchModel->model_id;}
	],

];