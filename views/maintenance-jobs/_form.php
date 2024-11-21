<?php

use app\helpers\FieldsHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="maintenance-jobs-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'maintenance-jobs-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['maintenance-jobs/validate']:	//для новых моделей
			//['maintenance-jobs/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

	<div class="row">
		<div class="col-8">
			<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-4">
			<?= FieldsHelper::Select2Field($form,$model, 'services_id', [
				'data' => app\models\Services::fetchNames(),
				'options' => [
					'placeholder' => 'Сервис отсутствует'
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-8">
			<?= FieldsHelper::MarkdownField($form,$model, 'description',['height'=>140]) ?>

		</div>
		<div class="col-4">
			<?= FieldsHelper::Select2Field($form,$model, 'schedules_id', [
				'data' => app\models\Schedules::fetchNames(),
				'options' => [
					'placeholder' => 'Расписание отсутствует'
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'reqs_ids', [
				'data' => app\models\MaintenanceReqs::fetchNames(),
				'options' => [
					'placeholder' => 'Никакие'
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'links',['lines'=>2]) ?>
		</div>
	</div>
	<?= FieldsHelper::Select2Field($form,$model, 'services_ids', [
		'data' => app\models\Services::fetchNames(),
		'options' => [
			'placeholder' => 'Сервисы не обслуживаются'
		],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	<?= FieldsHelper::Select2Field($form,$model, 'comps_ids', [
		'data' => app\models\Comps::fetchNames(),
		'options' => [
			'placeholder' => 'ОС/ВМ не обслуживаются'
		],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	<?= FieldsHelper::Select2Field($form,$model, 'techs_ids', [
		'data' => app\models\Techs::fetchNames(),
		'options' => [
			'placeholder' => 'Оборуд. не обслуживается'
		],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	<div class="float-end">
		<?= FieldsHelper::CheckboxField($form,$model,'archived') ?>
	</div>
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
