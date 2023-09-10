<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="org-struct-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'org-struct-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['org-struct/validate']:	//для новых моделей
			//['org-struct/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'org_id',[
			'data'=>\app\models\Partners::fetchNames()
	])?>
	
	<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'pup')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'id')->textInput(['maxlength' => true]) ?>


		
	

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
