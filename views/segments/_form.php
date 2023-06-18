<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
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
	
	<?= app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'description',['lines' => 4,]) ?>

	<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
