<?php

use app\components\DeleteObjectWidget;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

if (!empty($model->link_ports_id) && is_object($model->linkPort)) {
	$model->link_techs_id=$model->linkPort->techs_id;
	$model->link_arms_id=$model->linkPort->arms_id;
	//echo $model->link_techs_id."/".$model->link_arms_id;
};
/*
$switchToTech=<<<JS
	$("#type_switcher_arm").prop('checked',false);
   	$("#link_to_arm").hide();
   	$("#link_to_tech").show();
	$("#link_arms_id").val('').trigger('change');
	$("#ports-link_ports_id").val('').trigger('change');
JS;

$switchToArm=<<<JS
	$("#type_switcher_tech").prop('checked',false);
   	$("#link_to_tech").hide();
   	$("#link_to_arm").show();
	$("#link_techs_id").val('').trigger('change');
	$("#ports-link_ports_id").val('').trigger('change');
JS;
*/

$clearPort=<<<JS
	$("#ports-link_ports_id").val('').trigger('change');
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
	
	<?= $form->field($model, 'techs_id')->hiddenInput()->label(false)->hint(false); ?>
	
	<?php if (strlen($model->name) && (!$model->hasErrors('name'))) { ?>
		<?= $form->field($model, 'name')->hiddenInput()->label(false)->hint(false); ?>
		<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
	<?php } else { ?>
		<div class="row">
			<div class="col-md-3">
				<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
			</div>
			<div class="col-md-9">
				<?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
			</div>
		</div>
	<?php } ?>

	<h3>Соединено с</h3>
	
	<div class="row">
		<div id="link_to_tech" class="col-md-8">
			<?=	$form->field($model, 'link_techs_id')->widget(Select2::className(), [
				'data' => app\models\Techs::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите Устройство',
					'id'=>'link_techs_id',
					'onclear' => $clearPort,
				],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>

		<div id="link_to_port" class="col-md-4">
			<?= $form->field($model, 'link_ports_id')->widget(DepDrop::className(), [
				//'data' => app\models\Ports::fetchNames(),
				'type' => DepDrop::TYPE_SELECT2,
				'data' => is_object($model->linkPort)?
					\yii\helpers\ArrayHelper::map($model->linkPort->tech->ddPortsList,'id','name')
					:null,
				'options'=>[
					'placeholder' => 'Выберите '.$model->getAttributeLabel('link_ports_id'),
				],
				'select2Options' => ['pluginOptions' => ['allowClear' => true]],
				'pluginOptions' => [
					'depends'=>['link_techs_id','link_arms_id'],
					'allowClear' => true,
					'multiple' => false,
					'loading' => false,
					'url'=>\yii\helpers\Url::to(['/ports/port-list'])
				]
			]) ?>
		</div>
		
	</div>

	<div class="d-flex flex-row">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success me-auto']) ?>
		<?= $model->isNewRecord?'':DeleteObjectWidget::widget([
			'model'=>$model,
			'hideUndeletable'=>true,
			'options'=>['class'=>'align-self-end btn btn-danger'],
			'url'=>['/ports/delete','id'=>$model->id,'return'=>'previous'],
		]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
