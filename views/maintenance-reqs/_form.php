<?php

use app\helpers\FieldsHelper;
use app\models\MaintenanceReqs;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="maintenance-reqs-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'maintenance-reqs-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['maintenance-reqs/validate']:	//для новых моделей
			//['maintenance-reqs/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	<div class="row">
		<div class="col-3">
			<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
			<?= FieldsHelper::CheckboxField($form,$model, 'is_backup') ?>
			<?= FieldsHelper::CheckboxField($form,$model, 'spread_comps') ?>
			<?= FieldsHelper::CheckboxField($form,$model, 'spread_techs') ?>
		</div>
		<div class="col-9">
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'description') ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'links') ?>
		</div>
	</div>
	<?= FieldsHelper::Select2Field($form,$model,'includes_ids',[
		'data'=> MaintenanceReqs::fetchNames(),
		//'hintModel'=>'auto',
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	<?= FieldsHelper::Select2Field($form,$model,'included_ids',[
		'data'=> MaintenanceReqs::fetchNames(),
		//'hintModel'=>'auto',
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>
	
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
