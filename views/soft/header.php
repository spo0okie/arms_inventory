<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\StripedAlertWidget;
use app\components\widgets\page\CornerWidget;
use yii\helpers\Html;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */
?>

<div class="d-flex flex-wrap flex-row-reverse">
	<div class="d-flex flex-fill flex-row flex-nowrap">
		<div class="me-5">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="flex-fill">
			<?php CornerWidget::begin(['model'=>$model,'archivedOptions'=>['reload'=>true]]) ?>
				<h1>
					<?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
						'qtip_ttip'=>'Редактировать изображения этого ПО',
						'qtip_side'=>'top'
					]) ?>
				</h1>
			<?php CornerWidget::end() ?>
			<?php foreach ($model->scans??[] as $scan) echo $this->render('/scans/thumb',[
				'model'=>$scan,
				'soft_id'=>$model->id,
				'static_view'=>true
			]); ?>
		</div>
	</div>
</div>
