<?php

use app\components\Forms\ArmsForm;
use app\models\Users;
use yii\helpers\Html;



/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="comps-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
		'validateOnBlur' => true,
		'validateOnChange' => true,
		'validateOnSubmit' => true,
		'id'=>'comps-form'
	]); ?>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'domain_id')->select2() ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model,  'sandbox_id')->select2() ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model,  'user_id')->select2([
				'data' => Users::fetchWorking($model->user_id),
			]) ?>
		</div>
		<div class="col-md-8">
			<?= $form->field($model, 'admins_ids')->select2([
				'data' => Users::fetchWorking($model->admins_ids),
			]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'arm_id')->select2(['options'=>[
				'onchange'=>"$('#comps-form').yiiActiveForm('validateAttribute', 'comps-platform_id')",
			]]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'platform_id')->select2(['options'=>[
				'onchange'=>"$('#comps-form').yiiActiveForm('validateAttribute', 'comps-arm_id')",
			]]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'ignore_hw')->checkbox() ?>
		</div>
		<div class="col-md-6 float-end">
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'services_ids')->select2() ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'maintenance_reqs_ids')->select2() ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model,  'maintenance_jobs_ids')->select2() ?>
		</div>
	</div>


	<?= $form->field($model,  'comment')->text(['rows'=>2]) ?>

    <p>
        <span onclick="$('#comps_advanced_settings').toggle()" class="href">Расширенные настройки</span>
    </p>
    <div id="comps_advanced_settings" style="display: none">
		<div class="row">
			<div class="col-md-10">
				<?= $form->field($model, 'os') ?>
			</div>
			<div class="col-md-2">
				<?= $form->field($model, 'raw_version') ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<?= $form->field($model, 'ip')->textAutoresize(['rows' => 2]) ?>
			</div>
			<div class="col-md-6">
				<?= $form->field($model, 'mac')->textAutoresize(['rows' => 2]) ?>
			</div>
		</div>
        <?= $form->field($model, 'raw_hw')->textAutoresize(['rows' => 10]) ?>
		<?= $form->field($model, 'raw_soft')->textAutoresize(['rows' => 10]) ?>
		<?= $form->field($model, 'external_links')->textAutoresize(['rows' => 10]) ?>
    </div>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
