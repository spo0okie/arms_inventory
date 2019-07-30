<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */
/* @var $form yii\widgets\ActiveForm */

$places=\app\models\Places::fetchNames();
$places['']='- помещение не назначено -';
asort($places);

$hidden=' style="display:none" ';

?>

<div class="materials-form">

    <?php $form = ActiveForm::begin([
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'id' => 'materials-form',
	    'validationUrl' => $model->isNewRecord?['materials/validate']:['materials/validate','id'=>$model->id],
    ]); ?>

    <?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
	    'data' => \app\models\Contracts::fetchNames(),
	    'options' => ['placeholder' => 'Выберите документы',],
	    'toggleAllSettings'=>['selectLabel'=>null],
	    'pluginOptions' => [
		    'allowClear' => true,
		    'multiple' => true
	    ]
    ]) ?>

    <div class="row">
        <div class="col-md-3">
	        <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
			        'format' => 'yyyy-mm-dd'
		        ]
	        ]); ?>
        </div>
        <div class="col-md-2">
	        <?= $form->field($model, 'count')->textInput() ?>
        </div>
        <div class="col-md-7">
	        <?= $form->field($model, 'parent_id')->widget(Select2::className(), [
		        'data' => \app\models\Materials::fetchNames(),
		        'options' => ['placeholder' => 'Выберите источник этого материала',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ],
		        'pluginEvents' =>[
			        'change'=>'function(){
                        if ($("#materials-parent_id").val()) {
                            $("#materials-model-selector").hide();
                            $("#materials-model-hint").show();
                        } else {
                            $("#materials-model-selector").show();
                            $("#materials-model-hint").hide();
                        }
                    }'
		        ],

	        ]) ?>
            <div <?= empty($model->parent_id)?$hidden:'' ?> id="materials-model-hint" >
                Если выбран источник материала, то категория и модель те, же что и в источнике<br /><br />
            </div>

        </div>
    </div>


    <div class="row" id="materials-model-selector" <?= !empty($model->parent_id)?$hidden:'' ?>>
        <div class="col-md-6">
	        <?= $form->field($model, 'type_id')->widget(Select2::className(), [
		        'data' => \app\models\MaterialsTypes::fetchNames(),
		        'options' => ['placeholder' => 'Выберите тип',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => false,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'model')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'places_id')->dropDownList($places) ?>
        </div>
    </div>






    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
