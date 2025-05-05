<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="soft-lists-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'descr') ?>
	
	<?= $form->field($model, 'comment')->text() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
