<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TechStates */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="tech-states-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-5">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'code') ?>
		</div>
		<div class="col-md-2 pt-3">
			<br />
			<?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'archived') ?>
		</div>
	</div>
	
    <?= \app\helpers\FieldsHelper::TextAutoresizeField($form, $model, 'descr',['lines' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
