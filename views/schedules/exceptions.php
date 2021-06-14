<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$daysSearchModel = new app\models\SchedulesEntriesSearch();
$daysSearchModel->schedule_id = $model->id;
$daysDataProvider = $daysSearchModel->search([]);

?>
<h2>Праздничные / внеочередные рабочие дни</h2>
<?= GridView::widget([
	'dataProvider' => $daysDataProvider,
	'filterModel' => $daysSearchModel,
	'columns' => [
		[
			'attribute'=>'date',
			'contentOptions' => function ($data) { return [
				'class' => ($data->date==Yii::$app->request->get('date'))?'success':'',
				'id'=>'day-'.$data->date
			];},
		],
		[
			'attribute'=>'schedule',
			'value'=>function($data){return $data->description;},
			'contentOptions' => function ($data) { return [
				'class' => ($data->date==Yii::$app->request->get('date'))?'success':'',
			];},
		],
		[
			'attribute'=>'comment',
			'contentOptions' => function ($data) { return [
				'class' => ($data->date==Yii::$app->request->get('date'))?'success':'',
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
]); ?>

<?= Html::a('Добавить праздничный / нестандартный рабочий день', [
	'/schedules-entries/create',
	'schedule_id' => $model->id,
], [
	'class' => 'btn btn-success'
]);?>
