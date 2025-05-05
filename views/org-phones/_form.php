<?php

use app\components\Forms\ArmsForm;
use app\models\Contracts;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="org-phones-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	Номер телефона, предоставляемый услугой связи
    <div class="row">
        <div class="col-md-2">
	        <?= $form->field($model, 'country_code') ?>
        </div>
        <div class="col-md-3">
	        <?= $form->field($model,'city_code') ?>
        </div>
        <div class="col-md-7">
	        <?= $form->field($model, 'local_code') ?>
        </div>
    </div>


	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model,'places_id')->select2() ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'services_id')->select2() ?>
		</div>

	</div>

    <div class="row">
        <div class="col-md-4">
	        <?= $form->field($model, 'account') ?>
        </div>
		<div class="col-md-4">
			<?= $form->field($model, 'cost') ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'charge')
				->classicHint(Contracts::chargeCalcHtml('orgphones','cost','charge'))
			?>
		</div>
		<div class="col-md-2 pt-3">
			<br />
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
    </div>

    <?= $form->field($model, 'comment')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
