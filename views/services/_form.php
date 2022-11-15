<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$segmentPlaceholder='Выберите сегмент ИТ инфраструктуры предприятия';
$schedulePlaceholder='Выберите расписание';
$responsiblePlaceholder='Выберите ответственного';
$supportPlaceholder='Выберите сотрудников';
$parentPlaceholder=' (насл. из основного сервиса/услуги)';

if (!$model->is_service) $model->is_service=0;

$changeParent= <<<JS
Select2UpdatePlaceholder = function (field,newPlaceholder,defaultValue) {
    if (!newPlaceholder) {
        newPlaceholder=defaultValue;
    } else {
        newPlaceholder=newPlaceholder+'$parentPlaceholder';
    }
	var \$select2 = $('#'+field).data('krajeeSelect2');
	var \$options = $('#'+field).data('s2Options');
	window[\$select2].placeholder = newPlaceholder;
	if (jQuery('#'+field).data('select2')) { jQuery('#'+field).select2('destroy'); }
	jQuery.when(jQuery('#'+field).select2(window[\$select2])).done(initS2Loading(field,\$options));

};

function changeServiceParent(parent_id) {
    
    $.ajax({url: "/web/services/json-preview?id="+parent_id})
	.done(function(data) {
		Select2UpdatePlaceholder('services-segment_id',data.segmentName,'$segmentPlaceholder');
		Select2UpdatePlaceholder('services-support_schedule_id',data.supportScheduleName,'$schedulePlaceholder');
		Select2UpdatePlaceholder('services-providing_schedule_id',data.providingScheduleName,'$schedulePlaceholder');
		Select2UpdatePlaceholder('services-responsible_id',data.responsibleName,'$responsiblePlaceholder');
		Select2UpdatePlaceholder('services-support_ids',data.supportNames,'$supportPlaceholder');
	})
	.fail(function () {
	    console.log("Ошибка получения данных!")
		Select2UpdatePlaceholder('services-segment_id','','$segmentPlaceholder');
		Select2UpdatePlaceholder('services-support_schedule_id','','$schedulePlaceholder');
		Select2UpdatePlaceholder('services-providing_schedule_id','','$schedulePlaceholder');
		Select2UpdatePlaceholder('services-responsible_id','','$responsiblePlaceholder');
		Select2UpdatePlaceholder('services-support_ids','','$supportPlaceholder');
	});
		
}
JS;
$this->registerJs($changeParent, yii\web\View::POS_END);

?>

<div class="services-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-5">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'parent_id', [
				'data' => \app\models\Services::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите основной сервис/услугу',
					'onchange' => 'changeServiceParent($(this).val());'
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'is_service')->radioList([0=>'Услуга',1=>'Сервис'],[
				'class'=>'input-group',
				'item' => function ($index, $label, $name, $checked, $value) use ($model){
					$id_prefix=Html::getInputId($model, 'is_service');
					return
						Html::radio($name, $checked, ['value' => $value, 'class' => 'btn-check','id'=>"{$id_prefix}_$index"]).
						'<label class="btn btn-outline-secondary" for="'.$id_prefix.'_'.$index.'">' . $label . '</label>';
				},
			]) ?>
		</div>
	</div>

    <div class="row">
        <div class="col-md-4">
	        <?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'is_end_user') ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'segment_id', [
				'data' => \app\models\Segments::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->segmentName))?
						$model->parentService->segmentName.$parentPlaceholder:$segmentPlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'providing_schedule_id', [
				'data' => \app\models\Schedules::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->providingScheduleName))?
						$model->parentService->providingScheduleName.$parentPlaceholder:$schedulePlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'support_schedule_id', [
				'data' => \app\models\Schedules::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->supportScheduleName))?
						$model->parentService->supportScheduleName.$parentPlaceholder:$schedulePlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'responsible_id', [
				'data' => \app\models\Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->responsibleName))?
						$model->parentService->responsibleName.$parentPlaceholder:$responsiblePlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'places_id', [
				'data' => \app\models\Places::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
				],
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'partners_id', [
				'data' => \app\models\Partners::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
				],
			]) ?>
			<div class="row">
				<div class="col-md-3">
					<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'currency_id', [
						'data' => \app\models\Currency::fetchNames(),
						'options' => ['placeholder' => 'RUR'],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						],
					]) ?>
				</div>
				<div class="col-md-6">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model,'cost') ?>
				</div>
				<div class="col-md-3">
					<?= $form->field($model,'charge')->textInput()->hint(\app\models\Contracts::chargeCalcHtml('services','cost','charge')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model,'weight') ?>
				</div>
				<div class="col-md-6 p-3 align-content-end">
					<br/>
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'archived') ?>
				</div>
			</div>


		</div>
        <div class="col-md-8">
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'search_text',['lines' => 1,]) ?>
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'description',['lines' => 2,]) ?>
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'links',[	'lines' => 1,]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'support_ids', [
				'data' => \app\models\Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->supportNames))?
						$model->parentService->supportNames.$parentPlaceholder:$supportPlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'depends_ids', [
				'data' => \app\models\Services::fetchNames(),
				'options' => ['placeholder' => 'Выберите сервисы',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'comps_ids', [
				'data' => \app\models\Comps::fetchNames(),
				'options' => ['placeholder' => 'Выберите серверы',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'techs_ids', [
				'data' => \app\models\Techs::fetchNames(),
				'options' => ['placeholder' => 'Выберите оборудование',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'contracts_ids', [
				'data' => \app\models\Contracts::fetchNames(),
				'options' => ['placeholder' => 'Выберите документы',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
        </div>
    </div>

	<div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	</div>
	<?= $form->field($model, 'notebook')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>






    <?php ActiveForm::end(); ?>

</div>
