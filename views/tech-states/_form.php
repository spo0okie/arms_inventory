<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TechStates */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="tech-states-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-5">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= $form->field($model, 'code') ?>
		</div>
		<div class="col-md-2 mt-3">
			<br>
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
	</div>
	
    <?= $form->field($model, 'descr')->text() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
