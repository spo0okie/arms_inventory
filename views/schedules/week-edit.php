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
			//Если задано явно, то рисуем явно заданное с возможностью править и удалить
			if (is_object($schedule=$data->findDay($day))) {
				$update=$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>', [
					'/schedules-entries/update',
					'id' => $schedule->id,
				]);
				$delete=$static_view?'':Html::a('<span class="fas fa-trash"></span>', [
					'/schedules-entries/delete',
					'id' => $schedule->id,
				], [
					'data' => [
						'confirm' => 'Удалить этот день в расписании?',
						'method' => 'post',
					],
				]);
				$text='<span title="Расписание на этот день задано явно">'.$schedule->mergedSchedule.'</span>';
				return	'<strong>'.$text.' '.$update.' '.$delete.'</strong>';
			}
			
			$create=$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>', [
				'/schedules-entries/create',
				'schedule_id' => $data->id,
				'date' => $day,
			]);
			
			$schedule=$data->getWeekDayScheduleRecursive($day);
			
			return (
					is_object($schedule)?
						$text='<span title="Расписание на этот день наследуется">'.$schedule->mergedSchedule.'</span>'
						:
						$data->getDictionary('nodata')
				).' '.$create;
		},
		'contentOptions' => [
			'class' => ($day==Yii::$app->request->get('date'))?'table-success':'',
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

