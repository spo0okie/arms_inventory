<?php

use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sandboxes-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'sandboxes-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['sandboxes/validate']:	//для новых моделей
			//['sandboxes/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>


	<div class="row">
		<div class="col-8">
			<?= FieldsHelper::TextInputField($form,$model,  'name') ?>
		</div>
		<div class="col-2">
			<?= FieldsHelper::TextInputField($form,$model, 'suffix') ?>
		</div>
		<div class="col-2">
			<?= FieldsHelper::CheckboxField($form,$model, 'network_accessible') ?>
			<?= FieldsHelper::CheckboxField($form,$model, 'archived') ?>
		</div>
	</div>


	<div class="row">
		<div class="col-8">
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'notepad') ?>
		</div>
		<div class="col-4">
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'links') ?>
		</div>
	</div>
	
	
		

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
