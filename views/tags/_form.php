<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use kartik\color\ColorInput;

/**
 * @var yii\web\View $this
 * @var app\models\Tags $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="tags-form">

    <?php $form = ArmsForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'color')->widget(ColorInput::class, [
        'options' => ['placeholder' => '#RRGGBB'],
        'pluginOptions' => [
            'showInput' => true,
            'showInitial' => true,
            'showPalette' => true,
            'showSelectionPalette' => true,
            'preferredFormat' => 'hex',
            'palette' => [
                ['#FF0000', '#FF5733', '#FF8C00', '#FFD700', '#FFFF00'],
                ['#00FF00', '#00FF7F', '#00CED1', '#0000FF', '#4B0082'],
                ['#8B00FF', '#FF00FF', '#FF1493', '#C71585', '#808080'],
            ],
        ],
    ]) ?>
	<?= $form->field($model, 'slug') ?>


	<?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>


    <?= $form->field($model, 'archived')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Создать' : 'Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>