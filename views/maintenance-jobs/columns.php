<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\gridColumns\ExpandableCardColumn;
use kartik\markdown\Markdown;
use app\models\Schedules;

if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
	'name'=>[
		'value'=>function($data) use ($renderer){
			return $renderer->render('item',['model'=>$data]);
		},
	],
	'description',
	'schedule'=>[
		'value'=>function($data) use ($renderer){
			/** @var Schedules $schedule */
			if (is_object($schedule=$data->schedule)) {
				$descr=$schedule->description?
					$schedule->description:
					$schedule->workTimeDescription;
				return $renderer->render('/schedules/item',[
					'model'=>$data->schedule,
					'name'=>$descr
				]);
				
			} return null;
		},
	],
	'objects'=>[
		'class'=>ExpandableCardColumn::class,
		'value'=>function($data) use ($renderer){
			$output=[];
			foreach ($data->comps as $comp) {
				$output[$comp->sname]=$renderer->render('/comps/item',['model'=>$comp,'static_view'=>false]);
			}
			foreach ($data->techs as $tech) {
				$output[$tech->sname]=$renderer->render('/techs/item',['model'=>$tech,'static_view'=>false]);
			}
			foreach ($data->services as $service) {
				$output[$service->name]=$renderer->render('/services/item',['model'=>$service,'static_view'=>false]);
			}
			ksort($output);
			return implode('<br />',$output);
		},
	],
];