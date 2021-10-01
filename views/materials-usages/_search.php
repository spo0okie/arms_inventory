<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsagesSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="materials-usages-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'materials_id') ?>

    <?= $form->field($model, 'count') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'arms_id') ?>

    <?php // echo $form->field($model, 'techs_id') ?>

    <?php // echo $form->field($model, 'comment') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
