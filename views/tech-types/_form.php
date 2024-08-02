<?php

use app\helpers\FieldsHelper;
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
		<div class="col-md-4">
			<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::TextInputField($form,$model, 'prefix') ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::TextInputField($form,$model, 'code') ?>
		</div>
		<div class="col-md-2">
			<br>
			<?= FieldsHelper::CheckboxField($form,$model, 'hide_menu') ?>
		</div>
	</div>


	
	<div class="row">
		<div class="col-md-9">
			<?= FieldsHelper::TextAutoresizeField($form,$model,'comment',['lines' => 8,]) ?>

		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-header">
					Может выполнять роли
				</div>
				<div class="card-body">
					<?= FieldsHelper::CheckboxField($form,$model,'is_computer') ?>
					<?= FieldsHelper::CheckboxField($form,$model,'is_display') ?>
					<?= FieldsHelper::CheckboxField($form,$model,'is_ups') ?>
					<?= FieldsHelper::CheckboxField($form,$model,'is_phone') ?>
				</div>
			</div>
		</div>
	</div>



	<?= FieldsHelper::TextInputField($form,$model, 'comment_name') ?>

	<?= FieldsHelper::TextInputField($form,$model, 'comment_hint') ?>
	
	<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	
    <?php ActiveForm::end(); ?>


</div>
