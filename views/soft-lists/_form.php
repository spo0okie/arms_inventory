<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="soft-lists-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'soft-lists-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['soft-lists/validate']:	//для новых моделей
			//['soft-lists/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'comment')->textarea(['rows' => max(4, count(explode("\n", $model->comment)))]) ?>		
	<?php $this->registerJs("$('#soft-lists-comment').autoResize().trigger('change.dynSiz');"); ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
