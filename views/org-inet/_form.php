<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="org-inet-form">

    <?php $form = ActiveForm::begin([
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['org-inet/create']):\yii\helpers\Url::to(['org-inet/update','id'=>$model->id]),
    ]); ?>
    <div class="row">
        <div class="col-md-6">
			<div class="row">
				<div class="col-md-7">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
				</div>
				<div class="col-md-5">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'account') ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-7">
					<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'services_id', [
						'data' => \app\models\Services::fetchProviderNames(),
						'options' => ['placeholder' => 'Выберите услугу связи',],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>
				</div>
				<div class="col-md-3">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'cost') ?>
				</div>
				<div class="col-md-2">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'charge',[
						'staticHint'=>\app\models\Contracts::chargeCalcHtml('orginet','cost','charge')
					]) ?>
				</div>
				<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'comment',[
					'lines' => 2,
				]) ?>
			</div>

            <div class="form-group">
		        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'places_id', [
				'data' => \app\models\Places::fetchNames(),
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'networks_ids', [
				'data' => \app\models\Networks::fetchNames(),
				'options' => ['placeholder' => 'Выберите предоставляемую подсеть',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => true
				]
			]) ?>
			
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'history',[
				'lines' => 2,
			]) ?>
			<div class="float-end">
				<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'archived') ?>
			</div>
		</div>
    </div>





    <?php ActiveForm::end(); ?>

</div>
