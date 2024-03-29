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
    <?php $form = ActiveForm::begin(); ?>
	<?= $form->field($model,'override_id')->hiddenInput()->label(false)->hint(false); ?>
	<?= $form->field($model,'parent_id')->hiddenInput()->label(false)->hint(false); ?>
	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'start_date')->widget(DateControl::classname(), [
				'options' => ['placeholder' => 'Начало периода'],
				'type'=>DateControl::FORMAT_DATE,
				'pluginOptions'=>[
					'weekStart' => '1',
				]
			])->hint($model->getDictionary($model->override_id?'override_start':'period_start')); ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'end_date')->widget(DateControl::classname(), [
				'options' => ['placeholder' => 'Конец периода'],
				'type'=>DateControl::FORMAT_DATE,
				'pluginOptions'=>[
					'weekStart' => '1',
				]
			])->hint($model->getDictionary($model->override_id?'override_end':'period_end')); ?>
		</div>
	</div>
	
	<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>

	<div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ActiveForm::end(); ?>

</div>
