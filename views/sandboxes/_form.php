<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sandboxes-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model
	]); ?>


	<div class="row">
		<div class="col-8">
			<?= $form->field($model,  'name') ?>
		</div>
		<div class="col-2">
			<?= $form->field($model, 'suffix') ?>
		</div>
		<div class="col-2 mt-3">
			<?= $form->field($model, 'network_accessible')->checkbox() ?>
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
	</div>


	<div class="row">
		<div class="col-8">
			<?= $form->field($model, 'notepad')->text() ?>
		</div>
		<div class="col-4">
			<?= $form->field($model, 'links')->textAutoresize() ?>
		</div>
	</div>
	
	
		

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
