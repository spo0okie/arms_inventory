<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="org-phones-form">

    <?php $form = ActiveForm::begin(); ?>

	Номер телефона, предоставляемый услугой связи
    <div class="row">
        <div class="col-md-2">
	        <?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'country_code') ?>
        </div>
        <div class="col-md-3">
	        <?= \app\helpers\FieldsHelper::TextInputField($form,$model,'city_code') ?>
        </div>
        <div class="col-md-7">
	        <?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'local_code') ?>
        </div>
    </div>


	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'places_id', [
				'data' => \app\models\Places::fetchNames(),
				'options' => ['placeholder' => 'Выберите помещение',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'services_id', [
				'data' => \app\models\Services::fetchProviderNames(),
				'options' => ['placeholder' => 'Выберите услугу предоставляющую номер',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
		</div>

	</div>

    <div class="row">
        <div class="col-md-4">
	        <?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'account')->textInput(['maxlength' => true]) ?>
        </div>
		<div class="col-md-4">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'cost')->textInput() ?>
		</div>
		<div class="col-md-2">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'charge',[
				'classicHint'=>\app\models\Contracts::chargeCalcHtml('orgphones','cost','charge')
			])?>
		</div>
		<div class="col-md-2 pt-3">
			<br />
			<?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'archived')?>
		</div>
    </div>

    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
