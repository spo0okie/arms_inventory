<?php

use app\components\Forms\ArmsForm;
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
<div class="sms-form disable-on-submit">

    <?php $form = ArmsForm::begin([
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
			<?= $form->field($model,'phone') ?>
			<div class="form-group">
				<?= Html::submitButton('Отправить', ['class' => 'btn btn-success spinner-on-submit']) ?>
			</div>
		</div>
		<div class="col-9">
			<?= $form->field($model,'text')->textAutoresize() ?>
		</div>
	</div>

    <?php ArmsForm::end(); ?>

</div>
