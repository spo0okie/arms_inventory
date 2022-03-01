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

    <?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>


	<?= $form->field($model, 'arms_ids')->widget(Select2::className(), [
		'data' => \app\models\Arms::fetchNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 10,
	]) ?>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
