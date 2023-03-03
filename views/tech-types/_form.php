<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="tech-types-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'prefix') ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'code') ?>
		</div>
	</div>


	
	<div class="row">
		<div class="col-md-9">
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'comment',['lines' => 8,]) ?>

		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-header">
					Может выполнять роли
				</div>
				<div class="card-body">
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'is_computer') ?>
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'is_display') ?>
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'is_ups') ?>
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'is_phone') ?>
				</div>
			</div>
		</div>
	</div>



	<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'comment_name') ?>

	<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'comment_hint') ?>
	
	<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	
    <?php ActiveForm::end(); ?>


</div>
