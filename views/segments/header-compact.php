<?php

use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

?>
<div class="d-flex flex-row">
<div class="pe-5">
	<h1 class="text-nowrap">
		<?= LinkObjectWidget::widget([
			'model'=>$model,
			'hideUndeletable'=>false
		]) ?>
	</h1>
	<p class="mb-3"><?= \app\components\ModelFieldWidget::renderFieldValue($model,'code') ?></p>
</div>

<div class="p-0">
	<h4><?= $model->getAttributeLabel('description') ?></h4>
	<p>
		<?= \app\components\ModelFieldWidget::renderFieldValue($model,'description') ?>
	</p>
</div>
</div>
