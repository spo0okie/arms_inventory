<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Manufacturers */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="manufacturers-form">

    <?php $form = ArmsForm::begin([
        'id'=>'manufacturers-form',
        'enableAjaxValidation' => true,
        'validationUrl' => $model->isNewRecord?['manufacturers/validate']:['manufacturers/validate','id'=>$model->id],
		'model'=>$model
    ]); ?>

    <?= $form->field($model, 'id')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'full_name') ?>

    <?= $form->field($model, 'comment') ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
