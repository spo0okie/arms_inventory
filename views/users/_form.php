<?php

use app\components\CollapsableCardWidget;
use app\components\Forms\ArmsForm;
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

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'Ename') ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'Bday') ?>
				</div>
			</div>
			
			
			<div class="row">
				<div class="col-md-8">
					<?= $form->field($model, 'org_id') ?>

				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'employee_id') ?>

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


			<?= $form->field($model, 'Doljnost') ?>


			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'Login') ?>
				</div>
				<div class="col-md-8">
					<?= $form->field($model, 'Email') ?>
				</div>
			</div>


			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'Phone') ?>
				</div>
				<div class="col-md-8">
					<?= $form->field($model, 'work_phone') ?>
				</div>
			</div>


			
			<?= $form->field($model, 'Mobile') ?>
			
			<?= $form->field($model, 'private_phone') ?>
			
			
			<?= $form->field($model, 'manager_id') ?>
			
			<?= $form->field($model, 'Uvolen')->checkbox() ?>
			
			<?= $form->field($model, 'nosync')->checkbox() ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model,'ips')->textAutoresize()?>
			<?= $form->field($model, 'notepad')->text() ?>
		</div>
	</div>



    <div class="form-group mb-3">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

	<?= CollapsableCardWidget::widget([
		'title'=>'Дополнительно',
		'content'=>implode([
			$form->field($model,'uid'),
			$form->field($model,'employ_date'),
			$form->field($model,'resign_date'),
		]),
		'initialCollapse'=>true,
	])?>
	
    <?php ArmsForm::end(); ?>

</div>
