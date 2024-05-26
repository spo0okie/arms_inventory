<?php

use app\helpers\FieldsHelper;
use app\models\Domains;
use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Sandboxes;
use app\models\Services;
use app\models\Techs;
use app\models\Users;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;



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
		<div class="col-md-4">
			<?= FieldsHelper::Select2Field($form,$model, 'domain_id',[
				'data'=> Domains::fetchNames(),
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-4">
			<?= FieldsHelper::Select2Field($form,$model, 'sandbox_id',[
				'data'=> Sandboxes::fetchNames(),
			]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model,'arm_id', [
				'data' => Techs::fetchArmNames(),
				'options' => ['placeholder' => 'Выберите АРМ',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'user_id', [
				'data' => Users::fetchWorking($model->user_id),
				'options' => ['placeholder' => 'Пользователь АРМ',],
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
			<?= FieldsHelper::CheckboxField($form,$model, 'ignore_hw') ?>
		</div>
		<div class="col-md-6 float-end">
			<?= FieldsHelper::CheckboxField($form,$model,'archived') ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'services_ids', [
				'data' => Services::fetchNames(),
				'options' => ['placeholder' => 'Нет сервисов',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::Select2Field($form,$model, 'maintenance_reqs_ids', [
				'data' => MaintenanceReqs::fetchNames(),
				'options' => ['placeholder' => 'Получать из сервисов',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::Select2Field($form,$model, 'maintenance_jobs_ids', [
				'data' => MaintenanceJobs::fetchNames(),
				'options' => ['placeholder' => 'Отсутствуют',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
	</div>


	<?= FieldsHelper::TextAutoresizeField($form,$model, 'comment',['lines'=>2]) ?>

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
