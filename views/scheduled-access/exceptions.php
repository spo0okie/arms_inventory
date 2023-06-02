<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$daysSearchModel = new app\models\SchedulesEntriesSearch();
$daysSearchModel->schedule_id = $model->id;
$daysDataProvider = $daysSearchModel->search([]);
$renderer=$this;

if (!isset($acl_mode)) $acl_mode=(count($model->acls));



echo '<h2>Периоды предоставления / отзыва доступа</h2>';


echo Html::a('Добавить период предоставления/отзыва доступа', [
	'/schedules-entries/create',
	'schedule_id' => $model->id,
	'is_period' => 1,
], ['class' => 'btn btn-success']);


echo GridView::widget([
	'dataProvider' => $daysDataProvider,
	'filterModel' => $daysSearchModel,
	'rowOptions'=>function($model, $key, $index, $table) {return [
		'class' => $model->is_work?'bg-green-striped border-2':'bg-red-striped border-2',
	];},
	'columns' => [
		[
			'attribute'=>'date',
			'format'=>'raw',
			'value'=>function($data)use($renderer){
				return $data->is_period?
					$data->periodSchedule
					:
					'<span class="text-nowrap">'.Yii::$app->formatter->asDate($data->date,'dd.MM.yyyy (E)').'</span>'
					;
			},
		],
		[
			'attribute'=>'schedule',
			'format' => 'raw',
			'value' => function($data) {
				if ($data->is_period) return $data->isWorkDescription;
				$tokens=explode(',',$data->mergedSchedule);
				foreach ($tokens as $i=>$token)
					$tokens[$i]='<span class="text-nowrap">'.$token.'</span>';
				return implode(', ',$tokens);
				
			},
		],
		[
			'attribute'=>'comment',
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

