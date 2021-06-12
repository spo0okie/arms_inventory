<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="services-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-4">
	        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
	        <?= $form->field($model, 'is_end_user')->checkbox() ?>
			<?= $form->field($model, 'segment_id')->widget(Select2::className(), [
				'data' => \app\models\Segments::fetchNames(),
				'options' => ['placeholder' => 'Выберите сегмент ИТ инфраструктуры предприятия',],
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
				'lines' => 2,
			]) ?>
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'links',
				'lines' => 2,
			]) ?>

        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'providing_schedule_id')->widget(Select2::className(), [
		        'data' => \app\models\Schedules::fetchNames(),
		        'options' => ['placeholder' => 'Выберите расписание',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
	
	        <?= $form->field($model, 'responsible_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'Выберите ответственного',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
	
	        <?= $form->field($model, 'support_ids')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'Выберите сотрудников',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => true
		        ]
	        ]) ?>
			
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'support_schedule_id')->widget(Select2::className(), [
		        'data' => \app\models\Schedules::fetchNames(),
		        'options' => ['placeholder' => 'Выберите расписание',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
	
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
