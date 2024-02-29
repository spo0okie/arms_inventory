<?php

use app\models\SchedulesEntries;
use yii\bootstrap5\Alert;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;
$today=Yii::$app->request->get('date')?
	Yii::$app->request->get('date'):	//если явно передали дату, ориентируемся на нее
	strtotime("today");	//иначе на сегодня

?>


<?= $model->isNewRecord? Alert::widget([
	'body'=>'Для внесения изменений нужно сначала сохранить расписание',
	'options'=>['class' => 'alert-warning'],
	'closeButton'=>false
]):'' ?>

<?= GridView::widget([
	'dataProvider' => $model->getWeekDataProvider(),
	'summary' => false,
	'tableOptions' => [
		'class'=>'table table-condensed table-hover table-borderless'
	],
	'columns' => [
		[//название дня недели
			'header'=> SchedulesEntries::$label_day,
			'value'=>function($data,$day) {
				return SchedulesEntries::$days[$day];
			},
			'contentOptions' => function($data,$day) use ($model) {return[
				'class' => (
					$model->matchDate(Yii::$app->request->get('date'))
					&&
					$day==Yii::$app->request->get('entry')
				)?'table-success':'',
				'id'=>'day-'.$day
			];},
		],
		[//текстовое описание
			'header'=> SchedulesEntries::$label_schedule,
			'format'=>'raw',
			'value'=>function ($data,$day) use ($static_view,$model) {
				//Если задано явно, то рисуем явно заданное с возможностью править и удалить
				/**
				 * @var SchedulesEntries $data
				 */
				if (is_object($data) && ($data->date == $day) && ($data->schedule_id==$model->id)) {
					$update=$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>', [
						'/schedules-entries/update',
						'id' => $data->id,
					],['class'=>'open-in-modal-form']);
					$delete=$static_view?'':Html::a('<span class="fas fa-trash"></span>', [
						'/schedules-entries/delete',
						'id' => $data->id,
					], [
						'data' => [
							'confirm' => 'Удалить этот день в расписании?',
							'method' => 'post',
						],
					]);
					$text='<span title="Расписание на этот день задано явно">'.$data->mergedSchedule.'</span>';
					return	'<strong>'.$text.' '.$update.' '.$delete.'</strong>';
				} else {
					$create=$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>', [
						'/schedules-entries/create',
						'SchedulesEntries[schedule_id]' => $model->id,
						'SchedulesEntries[date]' => $day,
					],[
						'class'=>'open-in-modal-form',
						'data'=>['modal-pjax-reload'=>'auto',],
					]);
					return (
						is_object($data)?
							$text='<span title="Расписание на этот день наследуется">'.$data->mergedSchedule.'</span>'
							:
							$model->getDictionary('nodata')
						).' '.$create;
				}
			},
			'contentOptions' => function($data,$day) use ($model) {return[
				'class' => (
					$model->matchDate(Yii::$app->request->get('date'))
					&&
					$day==Yii::$app->request->get('entry')
				)?'table-success':''
			];},
		],
		[//диаграмма
			'header'=> SchedulesEntries::$label_graph,
			'value'=>function($data,$day) use ($renderer,$model) {
				return $renderer->render('/schedules-entries/stripe',['model'=>$data,'schedule'=>$model]);
			},
			'format'=>'raw',
			'contentOptions' => function($data,$day) use ($model) {return[
				'class' => (
					$model->matchDate(Yii::$app->request->get('date'))
					&&
					$day==Yii::$app->request->get('entry')
				)?'table-success schedule_graph':'schedule_graph'
			];},
		]
	]
]) ?>

<?php //var_dump($model->metaClasses);
