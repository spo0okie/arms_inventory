<?php

/**
 * Это рендер списка АРМов, вынесен отдельным файлом, т.к. нужен много где:
 * в списке АРМов
 *
 */


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\ExpandableCardWidget;
use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;
use app\models\Comps;
use app\models\Manufacturers;
use app\models\Techs;
use yii\helpers\Html;
if(!isset($static_view))$static_view=false;
$renderer = $this;
$manufacturers= Manufacturers::fetchNames();

return [
	'name' => [
		'value' => function ($data) use ($renderer,$static_view) {
			return $renderer->render('/comps/item', ['model' => $data,'icon'=>true,'static_view'=>$static_view]);
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
				/* @var $data Comps */
				foreach ($data->netIps as $ip) {
					$name=strtolower($ip->name);
					//выводим пояснение к IP только если он не поясняет про FQDN или hostname нашей ОС
					$sname=$ip->text_addr.(trim($name) && $name!=strtolower($data->name) && $name!=strtolower($data->fqdn)?' ('.$ip->name.')':'');
					$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'name'=>$sname]);
				}
				return ExpandableCardWidget::widget([
					'content'=>implode('<br />',$output)
				]);
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
		'model' => new Techs(),
		'value' => function ($data) use ($renderer) {
			return (is_object($data->arm)&&is_object($data->arm->place)) ?
				$renderer->render('/places/item', ['model' => $data->arm->place, 'full' => 1])
				: null;
		},
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
	'raw_hw' => [
		'value' => function ($data) use ($manufacturers) {
			if (is_object($data)) {
				/** @var Comps $data */
				return $this->render('/hwlist/shortlist',['model'=>$data->hwList,'arm_id'=>$data->arm_id]);
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
		'value' => function ($data) {return $data->os;},
	],
	'mac' => [
		'value'=>function ($data) {return ExpandableCardWidget::widget([
			'content'=> Html::tag(
				'span',
				Techs::formatMacs($data->mac,'<br />'),
				['class'=>'mac_address']
			)
		]);},
	],
	'raw_version',
	'updated_at',
	'comment',
	'effectiveMaintenanceReqs' => [
		'value' => function ($data) {return ModelFieldWidget::widget(['model'=>$data,'field'=>'effectiveMaintenanceReqs','title'=>false,'item_options'=>['static_view'=>true]]);},
	],
	'maintenanceJobs' => [
		'value' => function ($data) {return ModelFieldWidget::widget(['model'=>$data,'field'=>'maintenanceJobs','title'=>false,'item_options'=>['static_view'=>true]]);},
	],
];