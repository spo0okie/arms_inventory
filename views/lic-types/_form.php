<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */
/* @var $form yii\widgets\ActiveForm */
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
	
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 4,
	]) ?>
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'links',
		'lines' => 4,
	]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
