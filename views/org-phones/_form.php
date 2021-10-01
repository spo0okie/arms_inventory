<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="org-phones-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-2">
	        <?= $form->field($model, 'country_code')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
	        <?= $form->field($model, 'city_code')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-7">
	        <?= $form->field($model, 'local_code')->textInput(['maxlength' => true]) ?>
        </div>
    </div>


	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'places_id')->widget(Select2::className(), [
				'data' => \app\models\Places::fetchNames(),
				'options' => ['placeholder' => 'Выберите помещение',],
				//'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'cost')->textInput() ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'charge')->textInput()->hint(\app\models\Contracts::chargeCalcHtml('orgphones','cost','charge')) ?>
		</div>

	</div>

    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'prov_tel_id')->dropDownList(\app\models\ProvTel::fetchNames()) ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

	<?= $form->field($model, 'contracts_id')->widget(Select2::className(), [
		'data' => \app\models\Contracts::fetchNames(),
		'options' => ['placeholder' => 'Выберите документ',],
		//'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => false
		]
	]) ?>

    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
