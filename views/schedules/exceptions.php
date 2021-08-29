<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$daysSearchModel = new app\models\SchedulesEntriesSearch();
$daysSearchModel->schedule_id = $model->id;
$daysDataProvider = $daysSearchModel->search([]);
$renderer=$this;

if (!isset($acl_mode)) $acl_mode=(count($model->acls));


function selectClass($model){
	if (!empty($model->date) && ($model->date == Yii::$app->request->get('date')))
		return 'success';
	
	if (is_array($negative=Yii::$app->request->get('negative'))) {
		if (in_array($model->id,$negative))
			return 'danger';
	}
	
	if (is_array($positive=Yii::$app->request->get('positive'))) {
		if (in_array($model->id,$positive))
			return 'info';
	}

	return '';
}

if (!$acl_mode) {
	echo '<h2>Праздничные / внеочередные рабочие дни</h2>';
} else {
	echo '<h2>Периоды предоставления / отзыва доступа</h2>';
}

echo GridView::widget([
	'dataProvider' => $daysDataProvider,
	'filterModel' => $daysSearchModel,
	'columns' => [
		[
			'attribute'=>'date',
			'value'=>function($data)use($renderer){
				return $data->is_period?
					$data->periodSchedule
					:
					$data->date
					;
			},
			'contentOptions' => function ($data) { return [
				'class' => selectClass($data),
				'id'=>'day-'.$data->date.'-'.$data->date_end
			];},
		],
		[
			'attribute'=>'schedule',
			'value' => function($data) {
				return $data->is_period?
					($data->isWorkDescription)
					:
					$data->schedule;
			},
			'contentOptions' => function ($data) { return [
				'class' => selectClass($data),
			];},
		],
		[
			'attribute'=>'comment',
			'contentOptions' => function ($data) { return [
				'class' => selectClass($data),
			];},
		],
		
		[
			'class' => 'yii\grid\ActionColumn',
			'template'=>'{update}{delete}',
			'urlCreator'=>function ($action, $model, $key, $index, $column) {
				switch ($action) {
					case 'update':
						return \yii\helpers\Url::to(['/schedules-entries/update','id'=>$key]);
					case 'delete':
						return \yii\helpers\Url::to(['/schedules-entries/delete','id'=>$key]);
				};
				
			}
		],
	],
]);

if (!$acl_mode) {
	echo Html::a('Добавить нестандартный график', [
		'/schedules-entries/create',
		'schedule_id' => $model->id,
		'is_period' => 0,
	], ['class' => 'btn btn-success']);
	
	echo Html::a('Добавить раб/не раб. период', [
		'/schedules-entries/create',
		'schedule_id' => $model->id,
		'is_period' => 1,
	], ['class' => 'btn btn-success']);
} else {
	echo Html::a('Добавить период предоставления/отзыва доступа', [
		'/schedules-entries/create',
		'schedule_id' => $model->id,
		'is_period' => 1,
	], ['class' => 'btn btn-success']);
}
