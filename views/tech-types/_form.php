<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tech-types-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'prefix')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'comment')->textarea(['rows' => max(4,count(explode("\n",$model->comment)))]) ?>
	<?php $this->registerJs("$('#techtypes-comment').autoResize().trigger('change.dynSiz');"); ?>

	<?= $form->field($model, 'comment_name')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'comment_hint')->textInput(['maxlength' => true]) ?>
    <div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>


</div>
