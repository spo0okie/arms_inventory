<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $form yii\widgets\ActiveForm */

if (!isset($action)) $action='index';
if (!isset($userFilter)) $userFilter=\app\models\Users::fetchWorking();

?>

<?php $form = ActiveForm::begin([
	'action' => [$action],
	'method' => 'get',
	//'options'=>['class' =>'d-flex-inline flex-row flex-nowrap']
]); ?>
<div class="d-flex flex-row flex-nowrap">
	<div class="px-2 flex-fill">
		<?= $form->field($model, 'responsible_ids')->widget(Select2::className(), [
			'data' => $userFilter,
			'options' => [
				'placeholder' => 'фильтр',
				'tag'=>null,
			],
			'toggleAllSettings'=>['selectLabel'=>null],
			'pluginOptions' => [
				'allowClear' => true,
				'multiple' => true
			]
		])->label(false) ?>
	</div>


	<div class="px-2">
		<?= Html::submitButton('Отфильтровать', ['class' => 'btn btn-primary']) ?>
		<?= Html::resetButton('Сбросить фильтр', ['class' => 'btn btn-outline-secondary']) ?>
	</div>
	
</div>
	
	
	<?php ActiveForm::end(); ?>

