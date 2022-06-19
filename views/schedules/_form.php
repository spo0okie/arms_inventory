<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\datecontrol\DateControl;



/* @var $this yii\web\View */
/* @var $model app\models\Schedules */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

if (!isset($acl_mode)) $acl_mode=false;

?>

<div class="schedules-form">

    <?php
	$form = ActiveForm::begin();
    if (!$acl_mode) {
    
    	if ($model->isNewRecord) { ?>
			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
						'data' => \app\models\Schedules::fetchNames(),
						'options' => [
							'placeholder' => 'Выберите расписание',
							'onchange' => '$("#schedules-defaultitemschedule").prop("disabled",($(this).val()))',
						],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => true,
							'multiple' => false
						]
					]) ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'defaultItemSchedule')
						->textInput([
							'maxlength' => true,
							'onchange' => '$("#schedules-parent_id").prop("disabled",($(this).val()))',
							'onkeypress' => "this.onchange();",
   							'onpaste'    => "this.onchange();",
   							'oninput'    => "this.onchange();",
						])
						->hint(
							$model->getAttributeHint('defaultItemSchedule').'<br />Примеры расписаний: '.
							\app\models\SchedulesEntries::scheduleSamplesHtmlFor('schedules-defaultitemschedule')
						)
					?>
				</div>
			</div>
   
		<?php } else { ?>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
						'data' => \app\models\Schedules::fetchNames(),
						'options' => ['placeholder' => 'Выберите расписание',],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => true,
							'multiple' => false
						]
					]) ?>
				</div>
			</div>
   
		<?php } ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'start_date')->widget(DateControl::classname(), [
				'options' => ['placeholder' => 'Начало периода'],
				'type'=>DateControl::FORMAT_DATE,
				'pluginOptions'=>[
					'weekStart' => '1',
				]
			])->hint($model->getDictionary('period_start')); ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'end_date')->widget(DateControl::classname(), [
				'options' => ['placeholder' => 'Конец периода'],
				'type'=>DateControl::FORMAT_DATE,
				'pluginOptions'=>[
					'weekStart' => '1',
				]
			])->hint($model->getDictionary('period_end')); ?>
		</div>
	</div>
	<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>

	<?php } else { ?>
		<?= $form->field($model, 'name')
			->textInput(['maxlength' => true])
			->hint(\app\models\Acls::$scheduleNameHint)
		?>
		<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
			'showExport'=>false
		])->hint(\app\models\Acls::$scheduleHistoryHint) ?>

	<?php }?>
	
	

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
