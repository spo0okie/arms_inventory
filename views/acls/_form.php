<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="acls-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'acls-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['acls/validate']:	//для новых моделей
			//['acls/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'comps_id')->widget(Select2::className(), [
		'data' => \app\models\Comps::fetchNames(),
		'options' => ['placeholder' => 'Выберите ОС',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => false
		]
	]) ?>

	<?= $form->field($model, 'techs_id')->widget(Select2::className(), [
		'data' => \app\models\Techs::fetchNames(),
		'options' => ['placeholder' => 'Выберите оборудование',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => false
		]
	]) ?>

	<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>

	<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>

    <?php ActiveForm::end(); ?>

</div>
