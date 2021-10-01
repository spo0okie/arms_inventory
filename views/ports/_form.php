<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */
/* @var $form yii\widgets\ActiveForm */

if (!empty($model->link_ports_id) && is_object($model->linkPort))
	$model->link_techs_id=$model->linkPort->techs_id;

$switchToTech=<<<JS
	$("#type_switcher_arm").prop('checked',false);
   	$("#link_to_arm").hide();
   	$("#link_to_tech").show();
JS;
$switchToArm=<<<JS
	$("#type_switcher_tech").prop('checked',false);
   	$("#link_to_tech").hide();
   	$("#link_to_arm").show();
JS;

$armLinkSelected=<<<JS
	$("#link_techs_id").val('');
	$("#link_ports_id").val('');
JS;

$techLinkSelected=<<<JS
	$("#ports-link_arms_id").val('');
	if ($("#link_techs_id").val()) {
	   	$("#link_to_port").show();
	} else {
	   	$("#link_to_port").hide();
		$("#link_ports_id").val('');
	}
JS;


?>

<div class="ports-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'ports-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['ports/validate']:	//для новых моделей
			//['ports/validate','id'=>$model->id], //для существующих
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'techs_id')->widget(Select2::className(), [
				'data' => app\models\Techs::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите '.$model->getAttributeLabel('techs_id')
				],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
	</div>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
	<h3>Соединено с</h3>

	<?= Html::radio('Оборудование',!is_object($model->linkArm),[
			'id'=>'type_switcher_tech',
			'onclick'=>$switchToTech,
		]
	)?>
	<?= Html::label('С оборудованием','type_switcher_tech') ?>
	<span>&nbsp;</span>

	<?= Html::radio('Рабочее место',is_object($model->linkArm),[
			'id'=>'type_switcher_arm',
			'onclick'=>$switchToArm,
		]
	)?>
	<?= Html::label('С рабочим местом / сервером','type_switcher_arm') ?>
	
	
	<div id="link_to_arm" <?= is_object($model->linkArm)?'':'style="display:none"'?>>
		<?= $form->field($model, 'link_arms_id')->widget(Select2::className(), [
			'data' => app\models\Arms::fetchNames(),
			'options' => [
				'placeholder' => 'Выберите '.$model->getAttributeLabel('link_arms_id'),
				'onchange'=>$armLinkSelected,
			],
			'pluginOptions' => [
				'allowClear' => true,
				'multiple' => false
			]
    	]) ?>
	</div>
	<div id="link_to_tech" <?= is_object($model->linkArm)?'style="display:none"':''?>>
		<?=	$form->field($model, 'link_techs_id')->widget(Select2::className(), [
			'data' => app\models\Techs::fetchNames(),
			'options' => [
				'placeholder' => 'Выберите Устройство',
				'onchange' => $techLinkSelected,
				'id'=>'link_techs_id',
			],
			'pluginOptions' => [
				'allowClear' => true,
				'multiple' => false
			]
		]) ?>
		<div id="link_to_port" <?= empty($model->link_techs_id)?'style="display:none"':''?>>
			<?= $form->field($model, 'link_ports_id')->widget(DepDrop::className(), [
				//'data' => app\models\Ports::fetchNames(),
				'type' => DepDrop::TYPE_SELECT2,
				'data' => is_object($model->linkPort)?\yii\helpers\ArrayHelper::map($model->linkPort->tech->ddPortsList,'id','name'):null,
				'options'=>[
					'placeholder' => 'Выберите '.$model->getAttributeLabel('link_ports_id'),
				],
				'select2Options' => ['pluginOptions' => ['allowClear' => true]],
				'pluginOptions' => [
					'depends'=>['link_techs_id'],
					'allowClear' => true,
					'multiple' => false,
					'url'=>\yii\helpers\Url::to(['/techs/port-list'])
				]
			]) ?>
		</div>
	</div>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
