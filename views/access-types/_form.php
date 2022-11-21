<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<div class="access-types-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'access-types-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['access-types/validate']:	//для новых моделей
			//['access-types/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form, $model, 'name') ?>
		</div>
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form, $model, 'code') ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::CheckboxField($form, $model, 'is_app') ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::CheckboxField($form, $model, 'is_ip') ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::CheckboxField($form, $model, 'is_phone') ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::CheckboxField($form, $model, 'is_vpn') ?>
		</div>
	</div>
	
	<?= \app\helpers\FieldsHelper::TextInputField($form, $model, 'comment') ?>

	<?= \app\helpers\FieldsHelper::Select2Field($form, $model, 'children_ids',[
		'data'=>\app\models\AccessTypes::fetchNames(),
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form, $model, 'notepad',['rows' => 4]) ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
