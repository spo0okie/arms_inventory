<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use app\components\formInputs\DokuWikiEditor;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="maintenance-jobs-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-8">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-4">
			<?= $form->field($model, 'parent_id')->select2() ?>
		</div>
	</div>
	<div class="row">
		<div class="col-8">
			<?= $form->field($model, 'description')->text(['rows'=>6]) ?>

		</div>
		<div class="col-4">
			<?= $form->field($model, 'services_id')->select2() ?>
			<?= $form->field($model, 'schedules_id')->select2() ?>
			<?= $form->field($model, 'reqs_ids')->select2() ?>
			<?= $form->field($model, 'links')->textAutoresize(['rows'=>2]) ?>
		</div>
	</div>
	<?= $form->field($model, 'services_ids')->select2() ?>
	<?= $form->field($model, 'comps_ids')->select2() ?>
	<?= $form->field($model, 'techs_ids')->select2() ?>
	<div class="float-end">
		<?= $form->field($model,'archived')->checkbox() ?>
	</div>
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
