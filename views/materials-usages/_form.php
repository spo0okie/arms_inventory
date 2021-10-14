<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="materials-usages-form">

    <?php $form = ActiveForm::begin([
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['materials-usages/create']):\yii\helpers\Url::to(['materials-usages/update','id'=>$model->id]),
    ]); ?>

    <div class="row">
        <div class="col-md-7">
	        <?= $form->field($model, 'materials_id')->widget(Select2::className(), [
		        'data' => \app\models\Materials::fetchNames(),
		        'options' => ['placeholder' => 'Выберите расходуемый материал',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => false
		        ],
	        ]) ?>
        </div>
        <div class="col-md-2">
	        <?= $form->field($model, 'count')->textInput() ?>
        </div>
        <div class="col-md-3">
	        <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
			        'format' => 'yyyy-mm-dd',
					'weekStart' => '1',
		        ]
	        ]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
	        <?= $form->field($model, 'comment')->textInput() ?>
        </div>
        <div class="col-md-4">
	        <?= $form->field($model, 'arms_id')->widget(Select2::className(), [
		        'data' => \app\models\Arms::fetchNames(),
		        'options' => ['placeholder' => 'Выберите АРМ назначения',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => false
		        ],
	        ]) ?>
        </div>
        <div class="col-md-4">
	        <?= $form->field($model, 'techs_id')->widget(Select2::className(), [
		        'data' => \app\models\Techs::fetchNames(),
		        'options' => ['placeholder' => 'Выберите оборудование назначения',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => false
		        ],
	        ]) ?>
        </div>
    </div>




	<?= $form->field($model, 'history')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
