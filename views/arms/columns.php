<?php


/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


use kartik\grid\GridView;
use yii\helpers\Html;
use yii\web\JsExpression;

$renderer = $this;
$manufacturers=\app\models\Manufacturers::fetchNames();

return [
	
	'num' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/arms/item', ['model' => $data]);
		},
	],
	'model_name' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->techModel) ?
				$renderer->render('/tech-models/item', ['model' => $data->techModel, 'static_view' => true]) :
				null;
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
	'comp_ip' => [
		'modelAttribute' => 'ip',
		'model' => new \app\models\Comps(),
		'hint' => 'IP адреса <b>основной ОС</b> этого АРМ.<br>'.
		'Найти остальные ОС по IP можно через '.Html::a('список ОС',['/comps/index']).
		\app\models\ArmsModel::searchableOrHint,
		'value' => function ($data) use ($renderer) {
			if (is_object($data->comp)) {
				$output=[];
				foreach ($data->comp->netIps as $ip)
					$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);
				return implode(' ',$output);
			}
			return null;
		},
	],
	'comp_mac' => [
		'format' => 'ntext',
		'modelAttribute' => 'mac',
		'model' => new \app\models\Comps(),
		'hint' => 'MAC адреса <b>основной ОС</b> этого АРМ.<br>'.
			'Найти остальные ОС по MAC можно через '.Html::a('список ОС',['/comps/index']).
			\app\models\ArmsModel::searchableOrHint,
		'value' => function ($data) use ($renderer) {
			if (is_object($data->comp)) {
				return $data->comp->formattedMac;
			}
			return null;
		},
	],
	'user_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->user)?
				$renderer->render('/users/item', ['model' => $data->user]):
				null;
		},
	],
	'user_position' => [
		'label' => 'Должность',
		'value' => function ($data) use ($renderer) {
			return is_object($data->user) ?
				"<span class='arm_user_position'>{$data->user->Doljnost}</span>"
				: null;
		},
	],
	'places_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->place) ? $renderer->render('/places/item', ['model' => $data->place, 'full' => 1]) : null;
		},
	],
	'departments_id' => [
		'value' => function ($data) {
			return (is_object($data->user) && is_object($data->user->orgStruct)) ? $data->user->orgStruct->name:null;
		},
	],
	'attach' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/arms/item-attachments',['model'=>$data]);
		},
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
				'debug' => true,
				'multiple'=>true,
				'closeOnSelect' =>false,
				'selectionAdapter' => new JsExpression('$.fn.select2.amd.require("ArmsSelectionAdapter")'),
			],
		],
	],
	'comp_hw' => [
		'value' => function ($data)  {
			if (is_object($data->comp)) {
				$render=[];
				foreach ($data->comp->getHardArray() as $item)
					$render[]=$item->getName().' '.$item->getSN();
				return implode(' ',$render);
			}
			return null;
		},
		'contentOptions'=>function ($data) {
			$render=[];
			if (is_object($data->comp)) {
				foreach ($data->comp->getHardArray() as $item)
					$render[] = $item->getName() . ' ' . $item->getSN();
			}
			return [
				'class'=>'comp_hw_col',
				'qtip_ttip' => implode('<br />',$render)
			];
		}
	],
	'sn' => [
		'contentOptions'=>function ($data)  {
			$opts=['class'=>'sn_col'];
			if (isset($data->sn) && strlen($data->sn)) $opts['qtip_ttip']=$data->sn;
			return $opts;
		},
	],
	'inv_num' => [
		'contentOptions'=>function ($data) {
			$opts=['class'=>'inv_num_col'];
			if (isset($data->inv_num) && strlen($data->inv_num)) $opts['qtip_ttip']=$data->inv_num;
			return $opts;
		},
	],
	'inv_sn'=>[
		'value' => function ($data) use ($renderer) {
			$tokens=[];
			
			if (strlen($data->sn)) $tokens[]=$data->sn;
			if (strlen($data->inv_num)) $tokens[]=$data->inv_num;
			return \yii\helpers\Html::encode(implode(', ',$tokens));
			
		},
		'contentOptions'=>function ($data) {
			/* @var $data \app\models\Arms */
			return [
				'class'=>'inv_num_col',
				'qtip_ttip'=>
					'<strong>'.$data->getAttributeIndexLabel('sn').':</strong> '.($data->sn?$data->sn:'<i>отсутствует</i>').
					'<br />'.
					'<strong>'.$data->getAttributeIndexLabel('inv_num').':</strong> '.($data->inv_num?$data->inv_num:'<i>отсутствует</i>')
			];
		},
	
	]
];