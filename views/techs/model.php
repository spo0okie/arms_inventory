<?php

use app\components\ModelFieldWidget;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Techs */


?>
<div class="tech-model-view">
	<h3><?= ModelWidget::widget(['model'=>$model->model,'options'=>['long'=>1]]) ?></h3>
	
	<?php if ($model->model->contain_front_rack || $model->model->contain_back_rack) {
		echo ModelWidget::widget(['model'=>$model, 'view'=>'rack/rack', 'options'=>[]]);
	}?>
	
	<?= ModelWidget::widget(['model'=>$model->model->preview,'view'=>'ttip']) ?>
	<?= ModelFieldWidget::renderFieldTitle($model->model,'comment') ?>
	<p>
		<?= ModelFieldWidget::renderFieldValue($model->model,'comment') ?>
	</p>

	<br />

	<?php if ($model->model->individual_specs) { ?>
		<?= ModelFieldWidget::renderFieldTitle($model,'specs') ?>
		<p><?= ModelFieldWidget::renderFieldValue($model,'specs') ?></p>
		<br />
	<?php } ?>

	<?= ModelFieldWidget::renderFieldTitle($model->model,'links') ?>
	<p>
		<?= ModelFieldWidget::renderFieldValue($model->model,'links') ?>
	</p>
</div>


