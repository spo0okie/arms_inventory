<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<script>console.log('zjop!')</script>

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
	<div class="for-alert"></div>
	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'users_ids')->widget(Select2::classname(), [
				'data' => \app\models\Users::fetchWorking(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				],
			]) ?>
			
			<?= $form->field($model, 'comps_ids')->widget(Select2::classname(), [
				'data' => \app\models\Comps::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				],
			]) ?>
			
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'ips',
				'lines' => 1,
			]) ?>
			
			<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>

			<hr />
			<!-- https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail -->
			<?= $form->field($model, 'access_types_ids')->checkboxList(
				\app\models\AccessTypes::fetchNames(),
				[
					'class'=>"card d-flex flex-row pt-2 pb-1",
					'itemOptions'=>[
						'class'=>'p-2'
					],
				]
			);	?>
			
			<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>

		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>

		</div>
	</div>
	


    <?php ActiveForm::end(); ?>

</div>
