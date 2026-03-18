<?php

use app\components\IsArchivedObjectWidget;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Techs */
?>

<?= IsArchivedObjectWidget::widget(['model'=>$model,'title'=>'Это оборудование перенесено в архив']) ?>
<div class="tech-models-ttip ttip-row">
	<?php if (is_object($model->preview)) { ?>
		<div class="ttip-preview">
			<?= ModelWidget::widget(['model'=>$model->preview,'view'=>'ttip']) ?>
		</div>
	<?php } ?>
	<div class="ttip-card">
		<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	</div>
</div>


