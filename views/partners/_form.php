<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="partners-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'bname')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-8">
			<?= $form->field($model, 'uname')->textInput(['maxlength' => true]) ?>
		</div>
	</div>
	

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'inn')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'kpp')->textInput(['maxlength' => true]) ?>
		</div>
	</div>


	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'cabinet_url')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'support_tel')->textInput(['maxlength' => true]) ?>
		</div>
	</div>

	

    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
