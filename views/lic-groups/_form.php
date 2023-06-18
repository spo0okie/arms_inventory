<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="lic-groups-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'soft_ids')->widget(Select2::className(), [
				'data' => \app\models\Soft::listItemsWithPublisher(),
				'options' => ['placeholder' => 'Набирайте название для поиска',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => true,
				]
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'lic_types_id')->widget(Select2::className(), [
				'data' => \app\models\LicTypes::fetchNames(),
				'options' => ['placeholder' => 'Выберите схему',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>
	
	<?= $form->field($model, 'arms_ids')->widget(Select2::className(), [
		'data' => \app\models\Techs::fetchArmNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	
	<?= $form->field($model, 'users_ids')->widget(Select2::className(), [
		'data' => \app\models\Users::fetchWorking(),
		'options' => ['placeholder' => 'Выберите пользователей',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	
	<?= $form->field($model, 'comps_ids')->widget(Select2::className(), [
		'data' => \app\models\Comps::fetchNames(),
		'options' => ['placeholder' => 'Выберите операционные системы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= $form->field($model, 'linkComment')->textInput(['maxlength' => true]) ?>

	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'comment',[
		'lines' => 10,
	]) ?>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
