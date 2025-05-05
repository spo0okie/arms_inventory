<?php

use app\components\Forms\ArmsForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="maintenance-reqs-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>
	<div class="row">
		<div class="col-4">
			<?= $form->field($model, 'name') ?>
			<?= $form->field($model, 'is_backup')->checkbox() ?>
			<?= $form->field($model, 'spread_comps')->checkbox() ?>
			<?= $form->field($model, 'spread_techs')->checkbox() ?>
			<?= $form->field($model,'includes_ids')->select2() ?>
			<?= $form->field($model,'included_ids')->select2() ?>
		</div>
		<div class="col-8">
			<?= $form->field($model, 'description')->text(['height'=>140,'rows'=>10]) ?>
			<?= $form->field($model, 'links')->textAutoresize() ?>
		</div>
	</div>
	
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
