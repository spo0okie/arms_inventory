<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnectionsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="service-connections-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'initiator_id') ?>

    <?= $form->field($model, 'target_id') ?>

    <?= $form->field($model, 'initiator_details') ?>

    <?= $form->field($model, 'target_details') ?>

    <?php // echo $form->field($model, 'comment') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'updated_by') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
