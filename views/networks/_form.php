<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

if (!empty($model->dhcp)) $model->text_dhcp=(new PhpIP\IPv4($model->dhcp))->humanReadable();
if (!empty($model->router)) $model->text_router=(new PhpIP\IPv4($model->router))->humanReadable();


?>

<div class="networks-form">
	
	<?php $form = ArmsForm::begin([
		'enableClientValidation' => false,   //чтобы отключить валидацию через JS в браузере
		'enableAjaxValidation' => true,       //чтобы включить валидацию на сервере ajax запросы
		'id' => 'networks-form',
		'validationUrl' => $model->isNewRecord?['networks/validate']:['networks/validate','id'=>$model->id], //URL валидации на стороне сервера
		'model' => $model
	]); ?>
	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'text_addr') ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'name') ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model,  'segments_id')->select2() ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model, 'vlan_id')->select2() ?>
				</div>
			</div>
			<?= $form->field($model,  'comment') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model,  'text_router') ?>
			<?= $form->field($model,  'text_dhcp')->textAutoresize(['rows'=>2]) ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'ranges')->textAutoresize(['rows'=>2]) ?>
			<?= $form->field($model, 'links')->textAutoresize() ?>
			<?= $form->field($model, 'archived')->checkbox() ?>
		</div>
	</div>

	<?= $form->field($model, 'notepad')->text() ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
