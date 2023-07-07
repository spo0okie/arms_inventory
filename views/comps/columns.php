<?php

use kartik\grid\GridView;

/**
 * Это рендер списка АРМов, вынесен отдельным файлом, т.к. нужен много где:
 * в списке АРМов
 *
 */


/* @var $this yii\web\View */
/* @var $searchModel app\models\OldArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;
$manufacturers=\app\models\Manufacturers::fetchNames();

return [
	'name' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/comps/item', ['model' => $data,'icon'=>true,'static_view'=>false]);
		},
		'contentOptions'=>function ($data) {return [
			'class'=>'arm_hostname '.$data->updatedRenderClass
		];}
	
	],
	'arm_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->arm) ? $renderer->render('/techs/item', ['model' => $data->arm,'static_view'=>true]) : null;
		},
	],
	'ip' => [
		'value' => function ($data) use ($renderer) {
			if (is_object($data)) {
				$output=[];
				/* @var $data \app\models\Comps */
				foreach ($data->netIps as $ip) {
					$name=strtolower($ip->name);
					//выводим пояснение к IP только если он не поясняет про FQDN или hostname нашей ОС
					$sname=$ip->text_addr.(trim($name) && $name!=strtolower($data->name) && $name!=strtolower($data->fqdn)?' ('.$ip->name.')':'');
					$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'name'=>$sname]);
				}
				return implode(' ',$output);
			}
			return null;
		},
	],
	'user_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->arm) && is_object($data->arm->user)?
				$renderer->render('/users/item', ['model' => $data->arm->user]) : null;
		},
	],
	'user_position' => [
		'label' => 'Должность',
		'value' => function ($data) use ($renderer) {
			return is_object($data->arm) && is_object($data->arm->user)?
				"<span class='arm_user_position'>{$data->arm->user->Doljnost}</span>"
				: null;
		},
	],
	'places_id' => [
		'model' => new \app\models\Techs(),
		'value' => function ($data) use ($renderer) {
			return (is_object($data->arm)&&is_object($data->arm->place)) ?
				$renderer->render('/places/item', ['model' => $data->arm->place, 'full' => 1])
				: null;
		},
	],
	'services_ids' => [
		'value' => function ($data) use ($renderer) {
			return \app\components\ListObjectWidget::widget([
				'models'=>$data->services,
				'title'=>false,
				'item_options'=>['static_view'=>true],
			]);
		},
	],
	'raw_hw' => [
		'value' => function ($data) use ($manufacturers) {
			if (is_object($data)) {
				$render=[];
				foreach ($data->getHardArray() as $item)
					$render[]=$item->getName().' '.$item->getSN();
				return implode(' ',$render);
			}
			return null;
		},
		'contentOptions'=>function ($data) {
			$render=[];
			if (is_object($data)) {
				foreach ($data->getHardArray() as $item)
					$render[] = $item->getName() . ' ' . $item->getSN();
			}
			return [
				'class'=>'comp_hw_col',
				'qtip_ttip' => implode('<br />',$render)
			];
		}
	],
	'os' => [
		'label' => 'Софт',
	],
	'mac' => [
		'format' => 'ntext'
	],
	'raw_version',
	'updated_at',
];