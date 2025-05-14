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
				return $schedule->renderItem($this,['name'=>$descr]);
			} return null;
		},
	],
	'objects'=>[
		'class'=>ExpandableCardColumn::class,
		'value'=>function($data) use ($renderer){
			$output=[];
			foreach ($data->comps as $comp) {
				$output[$comp->sname]=$comp->renderItem($this,['static_view'=>false]);
			}
			foreach ($data->techs as $tech) {
				$output[$tech->sname]=$tech->renderItem($this,['static_view'=>false]);
			}
			foreach ($data->services as $service) {
				$output[$service->name]=$service->renderItem($this,['static_view'=>false]);
			}
			ksort($output);
			return implode('<br />',$output);
		},
	],
];