<?php

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\HistoryModel;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */
/* @var $jobs app\models\MaintenanceJobs */

if (!isset($static_view)) $static_view=false;

$cssClass='';
$ttip=['maintenance-reqs/ttip','id'=>$model->id];
if ($model instanceof HistoryModel) {
	$ttip['id']=$model->master_id;
	$ttip['timestamp']=$model->updated_at;
}

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	
	if (isset($model->absorbed) && $model->absorbed) $ttip['absorbedBy']=$model->absorbed;
	
	if (isset($jobs) && !$model->archived && !$model->absorbed) {	//архивные и поглощенные не проверяем
		$satisfied=false;
		foreach ($jobs as $job) if ($job->satisfiesReq($model)) {
			$satisfied=true;
			$ttip['satisfiedBy']=$job->id;
			break;
		}
		$cssClass=$satisfied?'bg-success link-light':'bg-danger link-light';
	}
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'archivedProperty'=>'archivedOrAbsorbed',
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'modal'=>true,
			'noDelete'=>true,
			'static'=>$static_view,
			'name'=>$name,
			//'nameSuffix'=>'',
			'noSpaces'=>true,
			'cssClass'=>$cssClass,
			'ttipUrl'=>Url::to($ttip),
		]),
	]);
} else echo "Отсутствует";