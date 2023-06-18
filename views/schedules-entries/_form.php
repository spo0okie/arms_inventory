<?php

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

    <?php $form = ActiveForm::begin([
		'id'=>$formId
	]); ?>
	
	<?= $form->field($model, 'is_period')->hiddenInput()->label(false)->hint(false); ?>
	
	<?= $form->field($model, 'is_work')->hiddenInput()->label(false)->hint(false); ?>
	
	
    <?php
	if ($model->schedule_id)
        echo $form->field($model, 'schedule_id')->hiddenInput()->label(false)->hint(false);
    else
        echo $form->field($model, 'schedule_id')->dropDownList(app\models\Schedules::fetchNames());
    
    if  ($model->is_period) { ?>
		<div class="row">
			<div class="col-md-4">
				<?= $form->field($model, 'date')->widget(DateControl::classname(), [
					'options' => ['placeholder' => 'Начало периода'],
					'type'=>DateControl::FORMAT_DATETIME,
					'pluginOptions'=>[
						'weekStart' => '1',
					]
				]); ?>
			</div>
			<div class="col-md-4">
				<?= $form->field($model, 'date_end')->widget(DateControl::classname(), [
					'options' => ['placeholder' => 'Конец периода'],
					'type'=>DateControl::FORMAT_DATETIME,
					'pluginOptions'=>[
						'weekStart' => '1',
					]
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
				<?php // $form->field($model, 'is_work')->checkbox() ?>
			</div>
		</div>
		<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
	<?php } else { ?>
		<?php if ($model->date) { ?>
			<?= $form->field($model, 'date')->hiddenInput()->label(false)->hint(false) ?>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'schedule')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
				</div>
			</div>
		<?php } else { ?>
			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'date')->widget(DatePicker::classname(), [
						'options' => ['placeholder' => 'Введите дату / день...'],
						'pluginOptions' => [
							'autoclose'=>true,
							'format' => 'yyyy-mm-dd'
						]
					]); ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'schedule')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
				</div>
			</div>
		<?php } ?>
	<?php } ?>
	
	
	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'history', [
		'lines' => 4,
	]) ?>


	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
