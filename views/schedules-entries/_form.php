<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;


/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="schedules-days-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<?= $form->field($model, 'is_period')->hiddenInput()->label(false)->hint(false); ?>
	<?= $form->field($model, 'is_work')->hiddenInput()->label(false)->hint(false); ?>
	
	
    <?php if ($model->schedule_id)
        echo $form->field($model, 'schedule_id')->hiddenInput()->label(false)->hint(false);
    else
        echo $form->field($model, 'schedule_id')->dropDownList(app\models\Schedules::fetchNames());
    ?>
	

    <?php if ($model->date) {
		echo $form->field($model, 'date')->hiddenInput()->label(false)->hint(false);
		//echo "<h3>Дата: {$model->day}</h3>";
    } else {
        echo $form->field($model, 'date')->widget(DatePicker::classname(), [
            'options' => ['placeholder' => 'Введите дату / день...'],
            'pluginOptions' => [
                'autoclose'=>true,
                'format' => 'yyyy-mm-dd'
            ]
        ]);
    } ?>

    <?= $form->field($model, 'schedule')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
	
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
