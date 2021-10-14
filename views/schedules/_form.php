<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;


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
    
    ?>

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
		<?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
		<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
			'showExport'=>false
		]) ?>
	</div>
 
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
