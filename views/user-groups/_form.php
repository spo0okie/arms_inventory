<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="user-groups-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

	        <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

	        <?= $form->field($model, 'ad_group')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">

	        <?= $form->field($model, 'users_ids')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchNames(),
		        'options' => ['placeholder' => 'Выберите сотрудников',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => true
		        ]
	        ]) ?>

	        <?= $form->field($model, 'notebook')->textarea(['rows' => 8]) ?>

        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
