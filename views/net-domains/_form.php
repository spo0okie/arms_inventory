<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-domains-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'places_id',[
				'data'=>\app\models\Places::fetchNames()
			]) ?>
		</div>
	</div>

    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
