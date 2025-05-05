<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<div class="access-types-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model
	]); ?>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'code') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'ip_params_def') ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<?= $form->field($model,  'is_app') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model,  'is_ip') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'is_phone') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'is_vpn') ?>
		</div>
	</div>
	
	<?= $form->field($model,  'comment') ?>

	<?= $form->field($model, 'children_ids') ?>

	<?= $form->field($model, 'notepad') ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
