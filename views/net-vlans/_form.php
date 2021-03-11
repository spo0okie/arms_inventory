<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="net-vlans-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'vlan')->textInput() ?>
	
	<?= $form->field($model, 'domain_id')->widget(Select2::className(), [
		'data' => \app\models\NetDomains::fetchNames(),
		'options' => [
			'placeholder' => 'Выберите L2 Домен',
		],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => false
		]
	]) ?>
	
	<?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
