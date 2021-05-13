<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="segments-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>
		</div>
	</div>
	
	<?= $form->field($model, 'description')->textarea(['rows' => max(4,count(explode("\n",$model->description)))]) ?>
	<?php $this->registerJs("$('#segments-description').autoResize().trigger('change.dynSiz');"); ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
