<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\datecontrol\DateControl;



/* @var $this yii\web\View */
/* @var $model app\models\Schedules */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="schedules-form">

    <?php $form = ActiveForm::begin(); ?>
	<?= $form->field($model, 'name')
		->textInput(['maxlength' => true])
		->hint(\app\models\Acls::$scheduleNameHint)
	?>

	<?= $form->field($model, 'history')->widget(\kartik\markdown\MarkdownEditor::className(), [
		'showExport'=>false
	])->hint(\app\models\Acls::$scheduleHistoryHint) ?>
	
	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
