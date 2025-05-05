<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ContractsStates */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="contracts-states-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-3">
			<?= $form->field($model,'code') ?>
		</div>
		<div class="col-7">
			<?= $form->field($model,'name') ?>
		</div>
		<div class="col-2 mt-3">
			<?= $form->field($model,'paid')->checkbox() ?>
			<?= $form->field($model,'unpaid')->checkbox() ?>
		</div>
	</div>
    
    <?= $form->field($model, 'descr')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
