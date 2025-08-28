<?php

use app\components\Forms\ArmsForm;
use app\models\Soft;
use app\models\Techs;
use yii\bootstrap5\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="lic-groups-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>
	

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'services_id')->select2() ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'soft_ids')->select2() ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'lic_types_id')->select2() ?>
		</div>
	</div>
	
	<?= $form->field($model, 'arms_ids')->select2([
		'data'=> Techs::fetchArmNames(),	//только АРМы
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model,  'users_ids')->select2([
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'comps_ids')->select2([
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	

	<?= $form->field($model, 'linkComment',['options'=>['style'=>'display:none','id'=>'linkComment']]) ?>

	<?= $form->field($model,'comment')->text(['rows' => 10,]) ?>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
