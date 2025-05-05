<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

?>

<div class="lic-types-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'descr') ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'name') ?>
		</div>
	</div>
	
	
	<?= $form->field($model, 'comment')->text(['rows' => 4]) ?>
	
	<?= $form->field($model,'links')->textAutoresize(['rows' => 2]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
