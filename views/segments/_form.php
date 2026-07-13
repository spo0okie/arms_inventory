<?php

use app\components\Forms\ArmsForm;
use app\components\formInputs\MarkdownEditorFix as MarkdownEditor;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="segments-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name') ?>
			<?= $form->field($model, 'code') ?>
			<?= $form->field($model, 'marker_id')->select2() ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model,'description') ?>
			<?= $form->field($model,'links')->textAutoresize() ?>
		</div>
	</div>
	<?= $form->field($model, 'history')->text() ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
