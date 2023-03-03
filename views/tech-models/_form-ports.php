<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

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
?>
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

