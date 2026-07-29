<?php

use app\components\Forms\ArmsForm;
use app\models\Users;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Notifications */
/* @var $form app\components\Forms\ArmsForm */
if (!isset($modalParent)) $modalParent = null;
?>

<div class="notification-form">

	<?php $form = ArmsForm::begin(['model' => $model]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'user_id')->select2([
				'data' => Users::fetchWorking($model->user_id),
			]) ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'event_key') ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'attempts') ?>
		</div>
	</div>

	<?= $form->field($model, 'subject') ?>

	<?= $form->field($model, 'body')->textarea(['rows' => 12]) ?>

	<div class="form-group">
		<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ArmsForm::end(); ?>

</div>
