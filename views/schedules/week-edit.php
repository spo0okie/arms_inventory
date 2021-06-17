<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;


$attr=[];

foreach (app\models\SchedulesEntries::$days as $day=>$name) {
	$attr[]=[
		'attribute'=>$name,
		'format'=>'raw',
		'value'=>function ($data) use ($day,$name,$static_view) {
			if (is_object($sched=$data->findDay($day))) {
				$update=$static_view?'':Html::a('Изменить', [
					'/schedules-entries/update',
					'id' => $sched->id,
				], [
					'class' => 'btn btn-primary btn-sm'
				]);
				$delete=$static_view?'':Html::a('Удалить', [
					'/schedules-entries/delete',
					'id' => $sched->id,
				], [
					'class' => 'btn btn-danger btn-sm',
					'data' => [
						'confirm' => 'Удалить этот день в расписании?',
						'method' => 'post',
					],
				]);
				return	$sched->schedule.' '.$update.' '.$delete;
			}
			
			$delete=$static_view?'':Html::a('Задать', [
				'/schedules-entries/create',
				'schedule_id' => $data->id,
				'date' => $day,
			], [
				'class' => 'btn btn-primary btn-sm'
			]);
			
			return (
				$day=='def'?
					'Не задано':
					(
					is_object($sched=$data->findDay('def'))?$sched->schedule.' (по умолч.)':'На задано'
					)
				).' '.$delete;
		},
		'contentOptions' => [
			'class' => ($day==Yii::$app->request->get('date'))?'success':'',
			'id'=>'day-'.$day
		],
	];
}
?>

<h2>Изменить рабочую неделю</h2>

<?= DetailView::widget([
	'model' => $model,
	'attributes' => $attr
]) ?>

