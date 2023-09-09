<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-vlans-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<div class="col-md-2">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'vlan') ?>
		</div>
		<div class="col-md-4">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'domain_id', [
				'data' => \app\models\NetDomains::fetchNames(),
				'options' => [
					'placeholder' => 'Выберите L2 Домен',
				],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>

	
	
	<?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
