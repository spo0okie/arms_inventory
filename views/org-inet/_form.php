<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="org-inet-form">

    <?php $form = ActiveForm::begin([
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['org-inet/create']):\yii\helpers\Url::to(['org-inet/update','id'=>$model->id]),
    ]); ?>
    <div class="row">
        <div class="col-md-6">
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'name')->textInput() ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model, 'cost')->textInput() ?>
				</div>
				<div class="col-md-2">
					<?= $form->field($model, 'charge')->textInput()->hint(\app\models\Contracts::chargeCalcHtml('orginet','cost','charge')) ?>
				</div>
			</div>
	
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'comment',
				'lines' => 2,
			]) ?>

			<?= $form->field($model, 'places_id')->widget(Select2::className(), [
		        'data' => \app\models\Places::fetchNames(),
		        //'options' => ['placeholder' => 'Статус рабочего места',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => false,
			        'multiple' => false
		        ]
	        ]) ?>

	        <?= $form->field($model, 'prov_tel_id')->widget(Select2::className(), [
		        'data' => \app\models\ProvTel::fetchNames(),
		        //'options' => ['placeholder' => 'Статус рабочего места',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => false,
			        'multiple' => false
		        ]
	        ]) ?>

	        <?= $form->field($model, 'contracts_id')->widget(Select2::className(), [
		        'data' => [null=>''] + \app\models\Contracts::fetchNames(),
		        //'options' => ['placeholder' => 'Статус рабочего места',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => false,
			        'multiple' => false
		        ]
	        ]) ?>

	        <?= $form->field($model, 'account')->textInput() ?>

            <div class="form-group">
		        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'type')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'static')->checkbox() ?>

	        <?= $form->field($model, 'ip_addr')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'ip_mask')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'ip_gw')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'ip_dns1')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'ip_dns2')->textInput(['maxlength' => true]) ?>
	
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'history',
				'lines' => 2,
			]) ?>

		</div>
    </div>





    <?php ActiveForm::end(); ?>

</div>
