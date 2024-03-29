<?php

use app\components\CollapsableCardWidget;
use app\models\OrgStruct;
use app\models\Partners;
use app\models\Users;
use kartik\depdrop\DepDrop;
use kartik\markdown\MarkdownEditor;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use app\helpers\FieldsHelper;
use yii\helpers\Url;

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
						'data' => Partners::fetchNames(),
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
					<?= $form->field($model, 'Orgeh')->widget(DepDrop::className(), [
						'data' => OrgStruct::fetchOrgNames($model->org_id),
						'type' => DepDrop::TYPE_SELECT2,
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
							'url'=> Url::to(['/org-struct/dep-drop']),
						]
					]) ?>

				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'Persg')->dropDownList(ArrayHelper::getColumn(Users::$WTypes,0)) ?>

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
			<?= $form->field($model, 'notepad')->widget(MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>
		</div>
	</div>



    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

	<?= CollapsableCardWidget::widget([
		'title'=>'Дополнительно',
		'content'=>implode([
			FieldsHelper::TextInputField($form,$model,'uid'),
			FieldsHelper::TextInputField($form,$model,'employ_date'),
			FieldsHelper::TextInputField($form,$model,'resign_date'),
		]),
		'initialCollapse'=>true,
	])?>
	
    <?php ActiveForm::end(); ?>

</div>
