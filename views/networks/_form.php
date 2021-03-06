<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $form yii\widgets\ActiveForm */

if (!empty($model->dhcp)) $model->text_dhcp=(new PhpIP\IPv4($model->dhcp))->humanReadable();
if (!empty($model->router)) $model->text_router=(new PhpIP\IPv4($model->router))->humanReadable();


?>

<div class="networks-form">
	
	<?php $form = ActiveForm::begin([
		'enableClientValidation' => false,   //чтобы отключить валидацию через JS в браузере
		'enableAjaxValidation' => true,       //чтобы включить валидацию на сервере ajax запросы
		'id' => 'networks-form',
		'validationUrl' => $model->isNewRecord?['networks/validate']:['networks/validate','id'=>$model->id], //URL валидации на стороне сервера
	]); ?>
	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'text_addr')->textInput() ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'vlan_id')->widget(Select2::className(), [
				'data' => app\models\NetVlans::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите VLAN',
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'segments_id')->widget(Select2::className(), [
				'data' => \app\models\Segments::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите Сегмент ИТ',
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'text_router')->textInput() ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'text_dhcp')->textInput() ?>
		</div>
	</div>




    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
