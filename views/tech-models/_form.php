<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tech-models-form">

    <?php $form = ActiveForm::begin([
        'id'=>'tech_models-edit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
	    'validationUrl' => $model->isNewRecord?['tech-models/validate']:['tech-models/validate','id'=>$model->id],
	    //'options' => ['enctype' => 'multipart/form-data'],
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['tech-models/create']):\yii\helpers\Url::to(['tech-models/update','id'=>$model->id]),
    ]); ?>

    <?php
        $js = '
        //меняем подсказку описания модели в при смене типа оборудования
        function techSwitchDescr(){
            techType=$("#techmodels-type_id").val();
            $.ajax({url: "/web/tech-types/hint-template?id="+techType})
                .done(function(data) {$("#comment-hint").html(data);})
                .fail(function () {console.log("Ошибка получения данных!")});
            }';
        $this->registerJs($js, yii\web\View::POS_BEGIN);
    ?>


    <div class="row">
        <div class="col-md-6" >
            <?= $form->field($model, 'type_id')->widget(Select2::className(), [
                'data' => \app\models\TechTypes::fetchNames(),
                'options' => [
                        'placeholder' => 'Выберите тип оборудования',
	                    'onchange' => 'techSwitchDescr();'
                    ],
                //'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
                    'allowClear' => false,
                    'multiple' => false
                ]
            ]) ?>
        </div>
        <div class="col-md-6" >
            <?= $form->field($model, 'manufacturers_id')->widget(Select2::className(), [
                'data' => \app\models\Manufacturers::fetchNames(),
                'options' => ['placeholder' => 'Выберите производителя',],
                //'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
                    'allowClear' => false,
                    'multiple' => false
                ]
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
	        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4" >
	        <?= $form->field($model, 'short')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
			<?= $form->field($model, 'comment')
				->textarea(['rows' => max(4,count(explode("\n",$model->comment)))])
				->hint(false) ?>
			<?= $form->field($model, 'individual_specs')->checkbox() ?>
        </div>
        <div class="col-md-4" >
			<label class="control-label" >
				Подсказка для описания модели
			</label>
			<br />
			<div id="comment-hint" class="hint-block">
				
				<?= is_null($model->type_id)?
					$model->getAttributeHint('comment'):
					Yii::$app->formatter->asNtext($model->type->comment)
				 ?>
			</div>
        </div>
    </div>

	<?php $this->registerJs("$('#techmodels-comment').autoResize();"); ?>

	<?= $form->field($model, 'links')->textarea(['rows' => 3]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
