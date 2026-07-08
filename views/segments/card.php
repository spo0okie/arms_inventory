<?php

use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

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
	<h4><?= $model->getAttributeLabel('description') ?></h4>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'description') ?>
</div>

<?php if (!$static_view && strlen($model->history)) { ?>
<div class="p-0">
	<!--	<hr/> -->
	<h4><?= $model->getAttributeLabel('history') ?></h4>
	<p>
		<?= \app\components\ModelFieldWidget::renderFieldValue($model,'history') ?>
	</p>
</div>
<?php } ?>
</div>
