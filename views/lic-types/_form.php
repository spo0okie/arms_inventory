<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;

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

	
	<?= $form->field($model, 'comment')->textarea(['rows' => max(4,count(explode("\n",$model->comment)))]) ?>

	<?= $form->field($model, 'links')->textarea(['rows' => max(4,count(explode("\n",$model->links)))]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
	<?php $this->registerJs("$('#lictypes-comment').autoResize().trigger('change.dynSiz');"); ?>

</div>
