<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

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
		<div class="col-md-10">
			<?= $form->field($model, 'responsible_ids')->widget(Select2::className(), [
				'data' => \app\models\Users::fetchWorking(),
				'options' => [
					'placeholder' => 'ограничить список пользователями',
					'tag'=>null,
				],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				]
			])->label(false) ?>

		</div>
		<div class="col-md-2">
			<div class="input-group">
				
				<?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
				<?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
			</div>
		</div>
		
	</div>
	
	
	<?php ActiveForm::end(); ?>

</div>
