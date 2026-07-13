<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Markers */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="markers-form">

	<?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-5">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= $form->field($model, 'comment') ?>
		</div>
		<div class="col-md-2 mt-3">
			<br>
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
	</div>

	<?php //цветовые пикеры подставит ColorType::renderInput, стиль рамки — ChoiceType ?>
	<div class="row">
		<div class="col-md-3">
			<?= $form->field($model, 'color') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'text_color') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'border_color') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'border_style') ?>
		</div>
	</div>

	<div class="form-group">
		<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ArmsForm::end(); ?>

</div>
