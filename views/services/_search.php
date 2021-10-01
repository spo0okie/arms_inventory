<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */

if (!isset($action))

?>

<div class="services-search">
	
	<?php $form = ActiveForm::begin([
		'action' => ['index'],
		'method' => 'get',
	]); ?>
	
	<?= $form->field($model, 'id') ?>
	
	<?= $form->field($model, 'schedule_id') ?>
	
	<?= $form->field($model, 'date') ?>
	
	<?= $form->field($model, 'schedule') ?>
	
	<?= $form->field($model, 'comment') ?>
	
	<?php // echo $form->field($model, 'created_at') ?>
	
	<div class="form-group">
		<?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
		<?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
	</div>
	
	<?php ActiveForm::end(); ?>

</div>
