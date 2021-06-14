<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;


/* @var $this yii\web\View */
/* @var $model app\models\Schedules */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="schedules-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
				'data' => \app\models\Schedules::fetchNames(),
				'options' => ['placeholder' => 'Выберите расписание',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>
    
    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'history',
		'lines' => 4,
	]) ?>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
