<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Domains */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="domains-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'fqdn') ?>

    <?= $form->field($model, 'comment') ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
