<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lic-groups-form">

    <?php $form = ActiveForm::begin(); ?>

	<?= $form->field($model, 'soft_ids')->widget(Select2::className(), [
		'data' => \app\models\Soft::listItemsWithPublisher(),
		'options' => ['placeholder' => 'Набирайте название для поиска',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => false,
			'multiple' => true,
		]
	]) ?>

	<?= $form->field($model, 'lic_types_id')->widget(Select2::className(), [
		'data' => \app\models\LicTypes::fetchNames(),
		'options' => ['placeholder' => 'Выберите продукт',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
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
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	
	<?= \app\widgets\TextAutoResizeWidget::widget([
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
