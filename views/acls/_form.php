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
	<div class="row">
		<div class="col-md-6">
			<h3>Выберите ресурс к которому предоставляется доступ</h3>
			<?= $form->field($model, 'comps_id')->widget(Select2::className(), [
				'data' => \app\models\Comps::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите ОС',
					'onchange' => 'if ($("select#acls-comps_id").val()) {'.
						'$("select#acls-techs_id").val("").trigger("change");'.
						'$("select#acls-ips_id").val("").trigger("change");'.
						'$("select#acls-services_id").val("").trigger("change"); }',
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false,
				]
			]) ?>
			
			<?= $form->field($model, 'techs_id')->widget(Select2::className(), [
				'data' => \app\models\Techs::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите оборудование',
					'onchange' => 'if ($("select#acls-techs_id").val()) {'.
						'$("select#acls-comps_id").val("").trigger("change");'.
						'$("select#acls-ips_id").val("").trigger("change");'.
						'$("select#acls-services_id").val("").trigger("change"); }',
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			
			<?= $form->field($model, 'ips_id')->widget(Select2::className(), [
				'data' => \app\models\NetIps::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите IP',
					'onchange' => 'if ($("select#acls-ips_id").val()) {'.
						'$("select#acls-comps_id").val("").trigger("change");'.
						'$("select#acls-techs_id").val("").trigger("change");'.
						'$("select#acls-services_id").val("").trigger("change"); }',
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			
			<?= $form->field($model, 'services_id')->widget(Select2::className(), [
				'data' => \app\models\Services::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите сервис',
					'onchange' => 'if ($("select#acls-services_id").val()) {'.
						'$("select#acls-comps_id").val("").trigger("change");'.
						'$("select#acls-ips_id").val("").trigger("change");'.
						'$("select#acls-tech_id").val("").trigger("change");}',
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<h3>Записная книжка по доступу к этому ресурсу</h3>
			<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>

		</div>
	</div>


	<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>

    <?php ActiveForm::end(); ?>

</div>
