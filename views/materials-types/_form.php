<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="materials-types-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-md-9">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-1">
			<?= $form->field($model, 'units') ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'code') ?>
		</div>
	</div>

    <?= $form->field($model, 'comment')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
