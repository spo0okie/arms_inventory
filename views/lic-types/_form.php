<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="lic-types-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
	</div>
	
	
	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'comment',[
		'lines' => 4,
	]) ?>
	
	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'links', [
		'lines' => 4,
	]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
