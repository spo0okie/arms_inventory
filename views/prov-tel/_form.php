<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProvTel */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="prov-tel-form">

	<?php $form = ActiveForm::begin([
		'id'=>'prov_tel-edit-form',
		//'enableClientValidation' => false,
		//'enableAjaxValidation' => true,
		//'validateOnBlur' => true,
		//'validateOnChange' => true,
		//'validateOnSubmit' => true,
		//'validationUrl' => $model->isNewRecord?['techs/validate']:['techs/validate','id'=>$model->id],
		//'options' => ['enctype' => 'multipart/form-data'],
		'action' => $model->isNewRecord?\yii\helpers\Url::to(['prov-tel/create']):\yii\helpers\Url::to(['prov-tel/update','id'=>$model->id]),
	]); ?>


    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cabinet_url')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'support_tel')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
