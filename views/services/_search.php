<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */

if (!isset($action)) $action='index';

?>

<div class="services-search">
	<?php $form = ActiveForm::begin([
		'action' => [$action],
		'method' => 'get',
	]); ?>
	<div class="row">
		<div class="col-md-12">
			<div class="input-group">
				<?= $form->field($model, 'responsible',['options'=>['tag'=>null]])->label(false) ?>
				<?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
				<?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
			</div>

		</div>
		
	</div>
	
	
	<?php ActiveForm::end(); ?>

</div>
