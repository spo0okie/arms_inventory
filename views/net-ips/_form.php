<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-ips-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-md-2">
			<?= $form->field($model, 'text_addr') ?>
		</div>
		
		<div class="col-md-3">
			<?= $form->field($model, 'name') ?>
		</div>
		
		<div class="col-md-7">
			<?= $form->field($model, 'comment') ?>
		</div>
	</div>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
