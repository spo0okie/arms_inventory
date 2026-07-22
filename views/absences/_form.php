<?php

use app\components\Forms\ArmsForm;
use app\models\Users;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Absences */
/* @var $form app\components\Forms\ArmsForm */
if (!isset($modalParent)) $modalParent = null;
?>

<div class="absences-form">

	<?php $form = ArmsForm::begin(['model' => $model]); ?>

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'user_id')->select2([
				'data' => Users::fetchWorking($model->user_id),
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'type') ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'date_from') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'date_to') ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'source') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'external_id') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'comment') ?>
		</div>
	</div>

	<div class="form-group">
		<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ArmsForm::end(); ?>

</div>
