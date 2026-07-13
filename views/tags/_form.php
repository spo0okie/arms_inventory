<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Tags $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="tags-form">

    <?php $form = ArmsForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?php //цветовой пикер подставит ColorType::renderInput ?>
    <?= $form->field($model, 'color') ?>
	<?= $form->field($model, 'slug') ?>


	<?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>


    <?= $form->field($model, 'archived')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Создать' : 'Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>