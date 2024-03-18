<?php

use app\components\HistoryWidget;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$daysSearchModel = new app\models\SchedulesEntriesSearch();
$daysSearchModel->schedule_id = $model->id;
$daysDataProvider = $daysSearchModel->search([]);
$renderer=$this;


echo '<h2>Периоды предоставления / отзыва доступа</h2>';


echo Html::a('Добавить период предоставления/отзыва доступа', [
	'/schedules-entries/create',
	'SchedulesEntries[schedule_id]' => $model->id,
	'SchedulesEntries[is_period]' => 1,
], ['class' => 'btn btn-success mb-1']);


echo GridView::widget([
	'dataProvider' => $daysDataProvider,
	//'filterModel' => $daysSearchModel,
	'layout'=>"{items}\n{pager}",
	'rowOptions'=>function($model) {return [
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
			'attribute'=>'updated_at',
			'format'=>'raw',
			'value'=>function($data){
				return HistoryWidget::widget(['model'=>$data,'prefix'=>'','showTime'=>false]);
			},
		],
		
		[
			'class' => 'yii\grid\ActionColumn',
			'template'=>'{update}{delete}',
			'urlCreator'=>function ($action, $model) {
				switch ($action) {
					case 'update':
						return Url::to(['/schedules-entries/update','id'=>$model->id]);
					case 'delete':
						return Url::to(['/schedules-entries/delete','id'=>$model->id]);
				}
				return null;
			}
		],
	],
]);

