<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use \app\models\OldArms;
use yii\bootstrap5\Modal;
use kartik\select2\Select2;



/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="comps-form">

    <?php $form = ActiveForm::begin([
		'enableClientValidation' => false,
		'enableAjaxValidation' => true,
		'validateOnBlur' => true,
		'validateOnChange' => true,
		'validateOnSubmit' => true,
		'validationUrl' => $model->isNewRecord?['comps/validate']:['comps/validate','id'=>$model->id],
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'domain_id',[
				'data'=>\app\models\Domains::fetchNames(),
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				],
			]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'arm_id', [
				'data' => \app\models\Techs::fetchArmNames(),
				'options' => ['placeholder' => 'Выберите АРМ',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'user_id', [
				'data' => \app\models\Users::fetchWorking($model->user_id),
				'options' => ['placeholder' => 'сотрудник не назначен',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'ignore_hw') ?>
		</div>
		<div class="col-md-6 float-end">
			<?=  \app\helpers\FieldsHelper::CheckboxField($form,$model,'archived') ?>
		</div>
	</div>
	
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'services_ids', [
		'data' => \app\models\Services::fetchNames(),
		'options' => ['placeholder' => 'Сервисы на этой ОС',],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'comment',['lines'=>2]) ?>

    <p>
        <span onclick="$('#comps_advanced_settings').toggle()" class="href">Расширенные настройки</span>
    </p>
    <div id="comps_advanced_settings" style="display: none">
        <?= $form->field($model, 'os')->textInput(['maxlength' => true]) ?>
		<div class="row">
			<div class="col-md-6">
				<?= $form->field($model, 'ip')->textarea(['rows' => 2]) ?>
			</div>
			<div class="col-md-6">
				<?= $form->field($model, 'mac')->textarea(['rows' => 2]) ?>
			</div>
		</div>
        <?= $form->field($model, 'raw_hw')->textarea(['rows' => 10]) ?>
		<?= $form->field($model, 'raw_soft')->textarea(['rows' => 10]) ?>
		<?= $form->field($model, 'external_links')->textarea(['rows' => 10]) ?>
    </div>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
