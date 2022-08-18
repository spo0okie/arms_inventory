<?php

use kartik\grid\GridView;

/**
 * Это рендер списка АРМов, вынесен отдельным файлом, т.к. нужен много где:
 * в списке АРМов
 *
 */


/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;
$manufacturers=\app\models\Manufacturers::fetchNames();

return [
	'name' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/comps/item', ['model' => $data,'icon'=>true]);
		},
		'contentOptions'=>function ($data) {return [
			'class'=>'arm_hostname '.$data->updatedRenderClass
		];}
	
	],
	'arm_id' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->arm) ? $renderer->render('/arms/item', ['model' => $data->arm]) : null;
		},
	],
	'ip' => [
		'value' => function ($data) use ($renderer) {
			if (is_object($data)) {
				$output=[];
				foreach ($data->netIps as $ip)
					$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);
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
		'model' => new \app\models\Arms(),
		'value' => function ($data) use ($renderer) {
			return (is_object($data->arm)&&is_object($data->arm->place)) ?
				$renderer->render('/places/item', ['model' => $data->arm->place, 'full' => 1])
				: null;
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