<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<div class="aces-form">
    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'aces-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['aces/validate']:	//для новых моделей
			//['aces/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	<div class="for-alert"></div>
	<?= $this->render('/aces/_form_layout', ['model' => $model,'form'=>$form]); ?>
	
	
	
	<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    <?php ActiveForm::end(); ?>

</div>
