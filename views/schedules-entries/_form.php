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
				<?= $form->field($model, 'is_work')->checkbox() ?>
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
	
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'history',
		'lines' => 4,
	]) ?>


	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
