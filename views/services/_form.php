<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */

$segmentPlaceholder='Выберите сегмент ИТ инфраструктуры предприятия';
$schedulePlaceholder='Выберите расписание';
$responsiblePlaceholder='Выберите ответственного';
$supportPlaceholder='Выберите сотрудников';
$parentPlaceholder=' (насл. из основного сервиса)';

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
		<div class="col-md-6">
			<?php echo $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
			<?php //echo $form->field($model, 'name')->widget(InputWidget::className(), []) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
				'data' => \app\models\Services::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите основной сервис',
					'onchange' => 'changeServiceParent($(this).val());'
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>

    <div class="row">
        <div class="col-md-4">
	        <?= $form->field($model, 'is_end_user')->checkbox() ?>
			<?= $form->field($model, 'segment_id')->widget(Select2::className(), [
				'data' => \app\models\Segments::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parent) && strlen($model->parent->segmentName))?
						$model->parent->segmentName.$parentPlaceholder:$segmentPlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= $form->field($model, 'providing_schedule_id')->widget(Select2::className(), [
				'data' => \app\models\Schedules::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parent) && strlen($model->parent->providingScheduleName))?
						$model->parent->providingScheduleName.$parentPlaceholder:$schedulePlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
			<?= $form->field($model, 'support_schedule_id')->widget(Select2::className(), [
				'data' => \app\models\Schedules::fetchNames(),
				'options' => [
					'placeholder' => (is_object($model->parent) && strlen($model->parent->supportScheduleName))?
						$model->parent->supportScheduleName.$parentPlaceholder:$schedulePlaceholder,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>

		</div>
        <div class="col-md-8">
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'description',
				'lines' => 3,
			]) ?>
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'links',
				'lines' => 3,
			]) ?>

        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
	
	        <?= $form->field($model, 'responsible_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parent) && strlen($model->parent->responsibleName))?
						$model->parent->responsibleName.$parentPlaceholder:$responsiblePlaceholder,
				],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
	
	        <?= $form->field($model, 'support_ids')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
				'options' => [
					'placeholder' => (is_object($model->parent) && strlen($model->parent->supportNames))?
						$model->parent->supportNames.$parentPlaceholder:$supportPlaceholder,
				],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => true
		        ]
	        ]) ?>
	
			<?= $form->field($model, 'archived')->checkbox() ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'depends_ids')->widget(Select2::className(), [
		        'data' => \app\models\Services::fetchNames(),
		        'options' => ['placeholder' => 'Выберите сервисы',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => true
		        ]
	        ]) ?>
			<?= $form->field($model, 'comps_ids')->widget(Select2::className(), [
				'data' => \app\models\Comps::fetchNames(),
				'options' => ['placeholder' => 'Выберите серверы',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
			<?= $form->field($model, 'techs_ids')->widget(Select2::className(), [
				'data' => \app\models\Techs::fetchNames(),
				'options' => ['placeholder' => 'Выберите оборудование',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>

        </div>
    </div>
	<?= $form->field($model, 'notebook')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>





    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
