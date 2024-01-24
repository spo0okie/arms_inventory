<?php

use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ContractsStates */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="contracts-states-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-3">
			<?= FieldsHelper::TextInputField($form,$model,'code') ?>
		</div>
		<div class="col-7">
			<?= FieldsHelper::TextInputField($form,$model,'name') ?>
		</div>
		<div class="col-2">
			<?= FieldsHelper::CheckboxField($form,$model,'paid') ?>
			<?= FieldsHelper::CheckboxField($form,$model,'unpaid') ?>
		</div>
	</div>
    
    <?= $form->field($model, 'descr')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
