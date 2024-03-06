<?php

use app\components\IsArchivedObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
?>

<?= IsArchivedObjectWidget::widget(['model'=>$model,'title'=>'Это оборудование перенесено в архив']) ?>
<div class="tech-models-ttip ttip-row">
	<?php if (is_object($model->preview)) { ?>
		<div class="ttip-preview">
			<?= $this->render('/scans/ttip',['model'=>$model->preview]) ?>
		</div>
	<?php } ?>
	<div class="ttip-card">
		<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	</div>
</div>
