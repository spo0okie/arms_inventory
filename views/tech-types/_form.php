<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="tech-types-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'prefix') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'code') ?>
		</div>
		<div class="col-md-2 mt-3">
			<br>
			<?= $form->field($model, 'hide_menu')->checkbox() ?>
		</div>
	</div>


	
	<div class="row">
		<div class="col-md-9">
			<?= $form->field($model,'comment')->textAutoresize(['rows' => 8,]) ?>

		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-header">
					Может выполнять роли
				</div>
				<div class="card-body">
					<?= $form->field($model,'is_computer')->checkbox() ?>
					<?= $form->field($model,'is_display')->checkbox() ?>
					<?= $form->field($model,'is_ups')->checkbox() ?>
					<?= $form->field($model,'is_phone')->checkbox() ?>
				</div>
			</div>
		</div>
	</div>



	<?= $form->field($model, 'comment_name') ?>

	<?= $form->field($model, 'comment_hint') ?>
	
	<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	
    <?php ArmsForm::end(); ?>


</div>
