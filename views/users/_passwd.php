<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use app\helpers\FieldsHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Users */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(); ?>
	<?= FieldsHelper::TextInputField($form,$model, 'password') ?>
	<?= FieldsHelper::TextInputField($form,$model, 'repeatPassword') ?>
    <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    <?php ActiveForm::end(); ?>

</div>
