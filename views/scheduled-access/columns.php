<?php

use app\components\ModelFieldWidget;
use app\models\Schedules;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
return [
	//Субъекты
	'objects'=>[
		'value'=>function($data) use ($renderer) {
			$aces=$data->aces;
			return ModelFieldWidget::widget([
				'models'=>$aces,
				'field'=>'subjects',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>['static_view'=>true,'icon'=>true,'no_class'=>true,'short'=>true],
				'glue'=>'<br>',
			]);
		}
	],
	'resources'=>[
		'value'=>function($data) use ($renderer) {
			return ModelFieldWidget::widget([
				'models'=>$data->acls,
				'field'=>'resource',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'lineBr'=>false,
				'item_options'=>['static_view'=>false,'icon'=>true,'no_class'=>true,'short'=>true],
				'glue'=>'<br>',
			]);
		}
	],
	'name'=>[
		'value'=>function($data) use ($renderer) {
			return $data->renderItem($renderer,['static_view'=>false,'noDelete'=>true]);
		}
	],
	'accessPeriods'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data Schedules
			 */
			$output=[];
			if (is_array($periods=$data->findPeriods(null,null)) && count($periods))
				foreach ($periods as $period) {
					$output[]=Html::tag(
						'span',
						str_replace(' ','&nbsp;',$period->periodSchedule),
						[
							'title'=>Yii::$app->formatter->asNtext($period->comment),
							'class'=>"text-nowrap p-1 ".($period->is_work?"bg-success":"bg-danger")
						]
					);
				}
			return implode('<br />',$output);
		},
		'contentOptions'=>function ($data) {
			/**
			 * @var $data Schedules
			 */
			$working=$data->isWorkTime( date('Y-m-d'),date('H:i:s'));
			return [
				'class'=>$working?'bg-green-striped border-2':'bg-red-striped border-2',
			];
		}
	],
	'acePartners'=>[
		'contentOptions'=>['glue'=>'<br>',]
	],
	'aclSegments'=>[
		'value'=>function($data) use ($renderer) {
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'aclSegments',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0 p-0'],
				'item_options'=>['static_view'=>true],
			]);
		}
	],
	'aceDepartments'=>[
		'contentOptions'=>['glue'=>'<br>',]
	],
	'aclSites'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data Schedules
			 */
			$items=[];
			if (count($data->aclSites)) foreach ($data->aclSites as $site) {
				$items[]=$this->render('/places/item',['model'=>$site,'static_view'=>true,'short'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
	'accessTypes'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data Schedules
			 */
			$items=[];
			foreach ($data->acls as $acl)
				foreach ($acl->aces as $ace )
					foreach ($ace->accessTypes as $type) {
						$items[$type->id]=$this->render('/layouts/item',['model'=>$type,'static_view'=>true]);
					}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
];
