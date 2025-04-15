<?php

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\StripedAlertWidget;
use yii\helpers\Html;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */
?>

<div class="d-flex flex-wrap flex-row-reverse">
	<div class="ms-5 d-flex">
		<div class="text-end opacity-75 small"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
		<div class="text-end ms-5"><?= ShowArchivedWidget::widget() ?></div>
	</div>
	<div class="d-flex flex-fill flex-row flex-nowrap">
		<div class="me-5">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="flex-fill">
			<h1 class="float-end">
				<?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
					'class'=>'float-end',
					'qtip_ttip'=>'Редактировать изображения этого ПО',
					'qtip_side'=>'top'
				]) ?>
			</h1>
			<?php foreach ($model->scans??[] as $scan) echo $this->render('/scans/thumb',[
				'model'=>$scan,
				'soft_id'=>$model->id,
				'static_view'=>true
			]); ?>
		</div>
	</div>
</div>
