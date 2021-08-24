<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aces-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'aces-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['aces/validate']:	//для новых моделей
			//['aces/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	<div class="row">
		<div class="col-md-6">
			<h3>Кому предоставляется</h3>
			<?= $form->field($model, 'users_ids')->widget(Select2::classname(), [
				'data' => \app\models\Users::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				],
			]) ?>
			
			<?= $form->field($model, 'comps_ids')->widget(Select2::classname(), [
				'data' => \app\models\Comps::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				],
			]) ?>
			
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'ips',
				'lines' => 4,
			]) ?>
			<hr />
			<?php
			/*echo $form->field($model, 'access_types_ids')->widget(Select2::classname(), [
				'data' => \app\models\AccessTypes::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => false,
					'multiple' => true
				],
			]);*/
			//https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail
			echo $form->field($model, 'access_types_ids')
				->checkboxList(\app\models\AccessTypes::fetchNames(),[
					'separator'=>'<br />',
				]);
			/*echo $form->field($model, 'access_types_ids')->widget(Select2::classname(), [
				'data' => \app\models\AccessTypes::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				],
			])*/
			?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
			
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
