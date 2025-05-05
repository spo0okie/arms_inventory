<?php

use app\components\Forms\ArmsForm;
use app\models\Contracts;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="org-inet-form">

    <?php $form = ArmsForm::begin([
	    'action' => $model->isNewRecord? Url::to(['org-inet/create']): Url::to(['org-inet/update','id'=>$model->id]),
		'model' => $model
    ]); ?>
    <div class="row">
        <div class="col-md-6">
			<div class="row">
				<div class="col-md-7">
					<?= $form->field($model, 'name') ?>
				</div>
				<div class="col-md-5">
					<?= $form->field($model, 'account') ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-7">
					<?= $form->field($model, 'services_id')->select2() ?>
				</div>
				<div class="col-md-3">
					<?= $form->field($model, 'cost') ?>
				</div>
				<div class="col-md-2">
					<?= $form->field($model, 'charge')
					->classicHint(Contracts::chargeCalcHtml('orginet','cost','charge')) ?>
				</div>
				<?= $form->field($model, 'comment')->text(['rows' => 2,]) ?>
			</div>

            <div class="form-group">
		        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <div class="col-md-6">
			<?= $form->field($model, 'places_id')->select2() ?>
			<?= $form->field($model,'networks_ids')->select2() ?>
			
			<?= $form->field($model, 'history')->text(['rows' => 2,]) ?>
			<div class="float-end">
				<?= $form->field($model,'archived')->checkbox() ?>
			</div>
		</div>
    </div>





    <?php ArmsForm::end(); ?>

</div>
