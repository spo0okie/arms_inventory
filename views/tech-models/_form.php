<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $form yii\widgets\ActiveForm */

$addPorts=<<<JS
for (
    let i = $('#port_min').val();
    i <= $('#port_max').val();
    i++
) {
    if ($('#techmodels-ports').val().length>0) {
	    $('#techmodels-ports').val(
    	    $('#techmodels-ports').val() + "\\n" + $('#port_prefix').val() + i
		)
    } else {
    	$('#techmodels-ports').val(
	        $('#port_prefix').val() + i
		)
    }
}

JS;

$formAction=$model->isNewRecord?
	['tech-models/create']:
	['tech-models/update','id'=>$model->id];

if (Yii::$app->request->get('return'))
	$formAction['return']=Yii::$app->request->get('return');

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
		//Вот это вот снизу зачем интересно? видимо для вставки в качестве модального окна
	    'action' => \yii\helpers\Url::to($formAction),
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
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'comment',
				'lines' => 4,
			]) ?>
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
	
	<?= $form->field($model, 'links')->textarea(['rows' => 3]) ?>

	<div class="row">
		<div class="col-md-8" >
			<?= $form->field($model, 'ports')->textarea(['rows' => 16]) ?>
		</div>
		<div class="col-md-4" >
			<h4>Добавить группу портов</h4>
			<?= Html::label('Начиная с номера','port_min') ?>
			<?= Html::textInput('port_min',1,['id'=>'port_min','class'=>'form-control','maxlength'=>3]) ?>
			<div class="hint-block">
				С какого номера начинается нумерация портов на устройстве. Иногда 0, иногда 1, иногда 2, если 1й порт называется WAN
			</div>
			<?= Html::label('До номера','port_max')?>
			<?= Html::textInput('port_max',16,['id'=>'port_max','class'=>'form-control','maxlength'=>3]) ?>
			<div class="hint-block">
				На каком номере заканчивается нумерация портов на устройстве (4/8/16/24/48/52)
			</div>
			<?= Html::label('С префиксом','port_prefix') ?>
			<?= Html::textInput('port_prefix','',['id'=>'port_prefix','class'=>'form-control']) ?>
			<div class="hint-block">
				Если порты на устройстве не просто пронумерованы, а с префиксом (LAN/Eth/Combo)
			</div>
			
			<?= Html::button('Добавить',[
				'class'=>'btn btn-default',
				'onClick'=>$addPorts
			]) ?>
		</div>
	</div>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
