<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Users */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'Ename')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'Bday')->textInput(['maxlength' => true]) ?>
				</div>
			</div>
			
			
			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'org_id')->widget(Select2::className(), [
						'data' => \app\models\Partners::fetchNames(),
						'options' => ['placeholder' => 'Организация',],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>

				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'employee_id')->textInput(['maxlength' => true]) ?>

				</div>
			</div>

			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'Orgeh')->widget(Select2::className(), [
						'data' => \app\models\OrgStruct::fetchNames(),
						'options' => ['placeholder' => 'Подразделение',],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>

				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'Persg')->dropDownList(\yii\helpers\ArrayHelper::getColumn(\app\models\Users::$WTypes,0)) ?>

				</div>
			</div>


			<?= $form->field($model, 'Doljnost')->textInput(['maxlength' => true]) ?>


			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'Login')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-8">
					<?= $form->field($model, 'Email')->textInput(['maxlength' => true]) ?>
				</div>
			</div>


			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'Phone')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-8">
					<?= $form->field($model, 'work_phone')->textInput(['maxlength' => true]) ?>
				</div>
			</div>


			
			<?= $form->field($model, 'Mobile')->textInput(['maxlength' => true]) ?>
			
			<?= $form->field($model, 'private_phone')->textInput(['maxlength' => true]) ?>
			
			
			<?= $form->field($model, 'manager_id')->textInput(['maxlength' => true]) ?>
			
			<?= $form->field($model, 'Uvolen')->checkbox() ?>
			
			<?= $form->field($model, 'nosync')->checkbox() ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>
		</div>
	</div>



    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
