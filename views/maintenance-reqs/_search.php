<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="maintenance-reqs-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'spread_comps') ?>

    <?= $form->field($model, 'spread_techs') ?>

    <?php // echo $form->field($model, 'links') ?>

    <?php // echo $form->field($model, 'changed_at') ?>

    <?php // echo $form->field($model, 'changed_by') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
