<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="net-vlans-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'vlan') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'domain_id')->select2() ?>
		</div>
	</div>
	
	<?= $form->field($model, 'comment')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
