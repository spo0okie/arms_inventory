<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="hw-ignore-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>
	
	<?= $form->field($model, 'comment') ?>

    <?= $form->field($model, 'fingerprint') ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
