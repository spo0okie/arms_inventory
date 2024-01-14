<?php

use app\helpers\FieldsHelper;
use app\models\Comps;
use app\models\Contracts;
use app\models\Currency;
use app\models\MaintenanceReqs;
use app\models\Partners;
use app\models\Places;
use app\models\Schedules;
use app\models\Segments;
use app\models\Services;
use app\models\Techs;
use app\models\Users;
use kartik\markdown\MarkdownEditor;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$segmentPlaceholder='Выберите сегмент ИТ инфраструктуры предприятия';
$schedulePlaceholder='Выберите расписание';
$responsiblePlaceholder='Выберите ответственного за сервис';
$supportPlaceholder='Выберите сотрудников поддержки';
$infrastructureResponsiblePlaceholder='Выберите ответственного за инфраструктуру';
$infrastructureSupportPlaceholder='Выберите сотрудников поддержки инфраструктуры';
$parentPlaceholder=' (насл. из основного сервиса/услуги)';

if (!$model->is_service) $model->is_service=0;

/** @noinspection JSUnusedLocalSymbols */
$changeParent= <<<JS
Select2UpdatePlaceholder = function (field,newPlaceholder,defaultValue) {
    if (!newPlaceholder) {
        newPlaceholder=defaultValue;
    } else {
        newPlaceholder=newPlaceholder+'$parentPlaceholder';
    }
    let \$field=$('#'+field);
	var \$select2 = \$field.data('krajeeSelect2');
	var \$options = \$field.data('s2Options');
	window[\$select2].placeholder = newPlaceholder;
	if (\$field.data('select2')) { \$field.select2('destroy'); }
	jQuery.when(\$field.select2(window[\$select2])).done(initS2Loading(field,\$options));

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
			<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= FieldsHelper::Select2Field($form,$model, 'parent_id', [
				'data' => Services::fetchNames(),
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
	        <?= FieldsHelper::CheckboxField($form,$model, 'is_end_user') ?>
			<?= FieldsHelper::Select2Field($form,$model, 'segment_id', [
				'data' => Segments::fetchNames(),
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
			<?= FieldsHelper::Select2Field($form,$model, 'providing_schedule_id', [
				'data' => Schedules::fetchNames(),
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
			<?= FieldsHelper::Select2Field($form,$model, 'support_schedule_id', [
				'data' => Schedules::fetchNames(),
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
			<?= FieldsHelper::Select2Field($form,$model,'responsible_id', [
				'data' => Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->responsibleName))?
						$model->parentService->responsibleName.$parentPlaceholder:$responsiblePlaceholder,
				],
				'hintModel'=>'Users',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model,'infrastructure_user_id', [
				'data' => Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->infrastructureResponsibleName))?
						$model->parentService->infrastructureResponsibleName.$parentPlaceholder:$infrastructureResponsiblePlaceholder,
				],
				'hintModel'=>'Users',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model,'places_id', [
				'data' => Places::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'hintModel'=>'Places',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
				],
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model,'partners_id', [
				'data' => Partners::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'hintModel'=>'Partners',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
				],
			]) ?>
			<div class="row">
				<div class="col-md-3">
					<?= FieldsHelper::Select2Field($form,$model,'currency_id', [
						'data' => Currency::fetchNames(),
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
					<?= FieldsHelper::TextInputField($form,$model,'cost') ?>
				</div>
				<div class="col-md-3">
					<?= $form->field($model,'charge')->textInput()->hint(Contracts::chargeCalcHtml('services','cost','charge')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model,'vm_cores') ?>
				</div>
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model,'vm_ram') ?>
				</div>
				<div class="col-md-4">
					<?= FieldsHelper::TextInputField($form,$model,'vm_hdd') ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= FieldsHelper::TextInputField($form,$model,'weight') ?>
				</div>
				<div class="col-md-6 p-3 align-content-end">
					<br/>
					<?= FieldsHelper::CheckboxField($form,$model, 'archived') ?>
				</div>
			</div>


		</div>
        <div class="col-md-8">
			<?= FieldsHelper::TextAutoresizeField($form,$model,'search_text',['lines' => 1,]) ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'description',['lines' => 2,]) ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'links',[	'lines' => 1,]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'support_ids', [
				'data' => Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->supportNames))?
						$model->parentService->supportNames.$parentPlaceholder:$supportPlaceholder,
				],
				'hintModel'=>'Users',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'infrastructure_support_ids', [
				'data' => Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parentService) && strlen($model->parentService->infrastructureSupportNames))?
						$model->parentService->infrastructureSupportNames.$parentPlaceholder:$infrastructureSupportPlaceholder,
				],
				'hintModel'=>'Users',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'depends_ids', [
				'data' => Services::fetchNames(),
				'options' => ['placeholder' => 'Выберите сервисы',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'hintModel'=>'Services',
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'comps_ids', [
				'data' => Comps::fetchNames(),
				'options' => ['placeholder' => 'Выберите серверы',],
				'hintModel'=>'Comps',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'techs_ids', [
				'data' => Techs::fetchNames(),
				'options' => ['placeholder' => 'Выберите оборудование',],
				'hintModel'=>'Techs',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'maintenance_reqs_ids', [
				'data' => MaintenanceReqs::fetchNames(),
				'options' => ['placeholder' => 'Выберите требования по обслуживанию',],
				'hintModel'=>'MaintenanceReqs',
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= FieldsHelper::Select2Field($form,$model, 'contracts_ids', [
				'data' => Contracts::fetchNames(),
				'options' => ['placeholder' => 'Выберите документы',],
				'hintModel'=>'Contracts',
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
	<?= $form->field($model, 'notebook')->widget(MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>






    <?php ActiveForm::end(); ?>

</div>
