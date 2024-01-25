<?php

use app\helpers\FieldsHelper;
use app\models\ui\SmsForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;


/* @var $this yii\web\View */
/* @var $model SmsForm */

if (!isset($modalParent)) $modalParent=null;

$title='Отправка SMS сообщения';

?>


<h1><?= $title ?></h1>
<div class="sms-form">

    <?php $form = ActiveForm::begin([
	    'id'=>'sms-form',
	    'enableClientValidation' => false,
	    //'enableAjaxValidation' => true,
	    //'validateOnBlur' => true,
	    //'validateOnChange' => true,
	    //'validateOnSubmit' => true,
	    //'validationUrl' => ['techs/rack-unit-validate'],
	    //'action' => ['techs/rack-unit','id'=>$model->id,'unit'=>$unit,'front'=>$front],
    ]); ?>
	<div class="row">
		<div class="col-3">
			<?= FieldsHelper::TextInputField($form,$model,'phone') ?>
			<div class="form-group">
				<?= Html::submitButton('Отправить', ['class' => 'btn btn-success']) ?>
			</div>
		</div>
		<div class="col-9">
			<?= FieldsHelper::TextAutoresizeField($form,$model,'text') ?>

		</div>
	</div>

    <?php ActiveForm::end(); ?>

</div>
