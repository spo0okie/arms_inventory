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
					<?= $form->field($model, 'name')->textInput() ?>
				</div>
				<div class="col-md-5">
					<?= $form->field($model, 'account')->textInput() ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-7">
					<?= $form->field($model, 'services_id')->widget(Select2::className(), [
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
					<?= $form->field($model, 'cost')->textInput() ?>
				</div>
				<div class="col-md-2">
					<?= $form->field($model, 'charge')->textInput()->hint(\app\models\Contracts::chargeCalcHtml('orginet','cost','charge')) ?>
				</div>
				<?= \app\components\TextAutoResizeWidget::widget([
					'form' => $form,
					'model' => $model,
					'attribute' => 'comment',
					'lines' => 2,
				]) ?>
			</div>




			


            <div class="form-group">
		        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <div class="col-md-6">
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'networks_id')->widget(Select2::className(), [
						'data' => \app\models\Networks::fetchNames(),
						'options' => ['placeholder' => 'Выберите предоставляемую подсеть',],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'places_id')->widget(Select2::className(), [
						'data' => \app\models\Places::fetchNames(),
						//'options' => ['placeholder' => 'Статус рабочего места',],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						]
					]) ?>
				</div>
			</div>

			
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
