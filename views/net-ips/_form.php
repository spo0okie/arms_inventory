<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-ips-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'net-ips-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['net-ips/validate']:	//для новых моделей
			//['net-ips/validate','id'=>$model->id], //для существующих
	]); ?>

    <?php // $form->field($model, 'addr')->textInput() ?>

    <?php // $form->field($model, 'mask')->textInput() ?>

	<div class="row">
		<div class="col-md-2">
			<?= $form->field($model, 'text_addr')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
		</div>
	</div>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
