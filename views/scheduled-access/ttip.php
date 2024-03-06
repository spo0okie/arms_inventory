<?php

use app\components\IsHistoryObjectWidget;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

?>
<div class="schedules-ttip ttip-card">
	<?= IsHistoryObjectWidget::widget(compact('model')) ?>
	<h1>
		<?= $model->name ?>
	</h1>
	
	<?php
	$daysSearchModel = new app\models\SchedulesEntriesSearch();
	$daysSearchModel->schedule_id = $model->id;
	$daysDataProvider = $daysSearchModel->search([]);
	$renderer=$this;
	

	echo GridView::widget([
		'dataProvider' => $daysDataProvider,
		'showHeader'=> false,
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
		],
	]);
	
	?>
</div>
