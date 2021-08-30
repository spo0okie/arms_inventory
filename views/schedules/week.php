<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$weekAttr=[];

for ($i=1; $i<=7; $i++) {
	$weekLabel=\app\models\SchedulesEntries::$days[$i];
	$weekAttr[]=[
		'label' => $weekLabel,
		'format' => 'raw',
		'value'=> $this->render('/schedules-entries/item',[
			'model'=>$model->getWeekDayScheduleRecursive($i)
		])
	];
}
if (!isset($static_view)) $static_view=false;


?>

<h2><?= implode('<br/>',$model->weekWorkTime) ?></h2>

