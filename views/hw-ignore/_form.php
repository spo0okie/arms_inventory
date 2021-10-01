<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="hw-ignore-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'fingerprint')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
