<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\date\DatePicker;
use kartik\datecontrol\DateControl;


/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
if (!isset($formId)) $formId='scheduleEntityForm';

$workToggle=<<<JS
	$("#schedulesentries-is_work").val($("#is_work_y").is(":checked")?1:0);
JS;

?>

<div class="schedules-days-form">

    <?php $form = ArmsForm::begin([
		'id'=>$formId
	]); ?>
	
	<?= $form->field($model, 'is_period')->hiddenInput()->label(false) ?>
	
	<?= $form->field($model, 'is_work')->hiddenInput()->label(false) ?>
	
	
    <?php
	if ($model->schedule_id)
        echo $form->field($model, 'schedule_id')->hiddenInput()->label(false);
    else
        echo $form->field($model, 'schedule_id')->select2();
    
    if  ($model->is_period) { ?>
		<div class="row">
			<div class="col-md-4">
				<?= $form->field($model, 'date')->date([
					'options' => ['placeholder' => 'Начало периода'],
					'type'=>DateControl::FORMAT_DATE,
				]); ?>
			</div>
			<div class="col-md-4">
				<?= $form->field($model, 'date_end')->date([
					'options' => ['placeholder' => 'Конец периода'],
					'type'=>DateControl::FORMAT_DATE,
				]); ?>
			</div>
			<div class="col-md-4">
				<label class="form-label"><?= $model->getAttributeLabel('is_work') ?></label><br />
				<div class="btn-group" role="group" >
					<input onchange='<?= $workToggle ?>' type="radio" class="btn-check" name="is_work" id="is_work_y" autocomplete="off" <?= $model->is_work?'checked':''?> >
					<label class="btn btn-outline-success" for="is_work_y"><?= $model->getAttributeLabel('is_work_Y')?></label>

					<input onchange='<?= $workToggle ?>' type="radio" class="btn-check" name="is_work" id="is_work_n" autocomplete="off"  <?= !$model->is_work?'checked':''?>>
					<label class="btn btn-outline-danger" for="is_work_n"><?= $model->getAttributeLabel('is_work_N')?></label>
				</div>
			</div>
		</div>
		<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
	<?php } else { ?>
		<?php if ($model->date) { ?>
			<?= $form->field($model, 'date')->hiddenInput()->label(false)->hint(false) ?>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'schedule') ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'comment')?>
				</div>
			</div>
		<?php } else { ?>
			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'date')->date([
						'options' => ['placeholder' => 'Введите дату / день...'],
					]); ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'schedule') ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'comment') ?>
				</div>
			</div>
		<?php } ?>
	<?php } ?>
	
	
	<?= $form->field($model, 'history')->text() ?>


	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
