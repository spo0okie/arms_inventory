<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="access-types-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'access-types-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['access-types/validate']:	//для новых моделей
			//['access-types/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'notepad')->textarea(['rows' => max(4, count(explode("\n", $model->notepad)))]) ?>		
	<?php $this->registerJs("$('#access-types-notepad').autoResize();"); ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
