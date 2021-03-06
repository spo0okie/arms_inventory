<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $form yii\widgets\ActiveForm */

$places=\app\models\Places::fetchNames();
$places['']='';
asort($places);
if ($model->parent_id)
?>

<div class="places-form">

    <?php $form = ActiveForm::begin(); ?>

	<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
		'data' => \app\models\Places::fetchNames(),
		'options' => ['placeholder' => 'Выберите родительское помещение',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => false
		]
	]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'short')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'addr')->textInput(['maxlength' => true,'placeholder'=>($model->parent_id)?$model->parent->addr:'']) ?>

    <?= $form->field($model, 'prefix')->textInput(['maxlength' => true,'placeholder'=>($model->parent_id)?$model->parent->prefix:'']) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
