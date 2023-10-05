<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use app\helpers\FieldsHelper;

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
					<?= FieldsHelper::TextInputField($form,$model, 'Ename') ?>
				</div>
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model, 'Bday') ?>
				</div>
			</div>
			
			
			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'org_id')->widget(Select2::className(), [
						'data' => \app\models\Partners::fetchNames(),
						'options' => [
							'placeholder' => 'Организация',
						],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>

				</div>
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model, 'employee_id') ?>

				</div>
			</div>

			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'Orgeh')->widget(\kartik\depdrop\DepDrop::className(), [
						'data' => \app\models\OrgStruct::fetchOrgNames($model->org_id),
						'type' => \kartik\depdrop\DepDrop::TYPE_SELECT2,
						'options' => [
							'placeholder' => 'Подразделение',
							
						],
						'select2Options' => [
							'pluginOptions' => [
								'dropdownParent' => $modalParent,
								'allowClear' => true,
								'multiple' => false,
							],
						],
						'pluginOptions' => [
							'depends'=>['users-org_id'],
							'url'=>\yii\helpers\Url::to(['/org-struct/dep-drop']),
						]
					]) ?>

				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'Persg')->dropDownList(\yii\helpers\ArrayHelper::getColumn(\app\models\Users::$WTypes,0)) ?>

				</div>
			</div>


			<?= FieldsHelper::TextInputField($form,$model, 'Doljnost') ?>


			<div class="row">
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model, 'Login') ?>
				</div>
				<div class="col-md-8">
					<?= FieldsHelper::TextInputField($form,$model, 'Email') ?>
				</div>
			</div>


			<div class="row">
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model, 'Phone') ?>
				</div>
				<div class="col-md-8">
					<?= FieldsHelper::TextInputField($form,$model, 'work_phone') ?>
				</div>
			</div>


			
			<?= FieldsHelper::TextInputField($form,$model, 'Mobile') ?>
			
			<?= FieldsHelper::TextInputField($form,$model, 'private_phone') ?>
			
			
			<?= FieldsHelper::TextInputField($form,$model, 'manager_id') ?>
			
			<?= $form->field($model, 'Uvolen')->checkbox() ?>
			
			<?= $form->field($model, 'nosync')->checkbox() ?>
		</div>
		<div class="col-md-6">
			<?= FieldsHelper::TextAutoresizeField($form,$model,'ips',['lines'=>4])?>
			<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>
		</div>
	</div>



    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

	<?= \app\components\CollapsableCardWidget::widget([
		'title'=>'Дополнительно',
		'content'=>FieldsHelper::TextInputField($form,$model,'uid'),
		'initialCollapse'=>true,
	])?>
	
    <?php ActiveForm::end(); ?>

</div>
