<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\LicItemsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lic-items-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'soft_id') ?>

    <?= $form->field($model, 'lic_group_id') ?>

    <?= $form->field($model, 'lic_type_id') ?>

    <?= $form->field($model, 'descr') ?>

    <?php // echo $form->field($model, 'count') ?>

    <?php // echo $form->field($model, 'comment') ?>

    <?php // echo $form->field($model, 'active_from') ?>

    <?php // echo $form->field($model, 'active_to') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
