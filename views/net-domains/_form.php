<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-domains-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'places_id')->select2() ?>
		</div>
	</div>

    <?= $form->field($model, 'comment')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
