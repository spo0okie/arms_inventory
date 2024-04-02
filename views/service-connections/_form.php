<?php

use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnections */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="service-connections-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'service-connections-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['service-connections/validate']:	//для новых моделей
			//['service-connections/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

	<?= FieldsHelper::TextAutoresizeField($form,$model, 'comment')?>

	<div class="row">
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form, $model, 'initiator_id', [
				'data' => app\models\Services::fetchNames(),
				'options' => ['placeholder' => 'Отсутствует'],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<div class="row">
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form, $model, 'initiator_comps_ids', [
						'data' => app\models\Comps::fetchNames(),
						'options' => ['placeholder' => 'Все ОС/ВМ'],
						'pluginOptions' => [
							'allowClear' => true,
							'multiple' => true
						]
					]) ?>
				</div>
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form, $model, 'initiator_techs_ids', [
						'data' => app\models\Techs::fetchNames(),
						'options' => ['placeholder' => 'Все оборудование'],
						'pluginOptions' => [
							'allowClear' => true,
							'multiple' => true
						]
					]) ?>
				</div>
			</div>
			<?= $form->field($model, 'initiator_details')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'target_id', [
				'data' => app\models\Services::fetchNames(),
				'options' => ['placeholder' => 'Отсутствует'],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<div class="row">
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form, $model, 'target_comps_ids', [
						'data' => app\models\Comps::fetchNames(),
						'options' => ['placeholder' => 'Все ОС/ВМ'],
						'pluginOptions' => [
							'allowClear' => true,
							'multiple' => true
						]
					]) ?>
				</div>
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form, $model, 'target_techs_ids', [
						'data' => app\models\Techs::fetchNames(),
						'options' => ['placeholder' => 'Все оборудование'],
						'pluginOptions' => [
							'allowClear' => true,
							'multiple' => true
						]
					]) ?>
				</div>
			</div>
			<?= $form->field($model, 'target_details')->textInput(['maxlength' => true]) ?>
		</div>
	</div>


    
		
	

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
