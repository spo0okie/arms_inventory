<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\ManufacturersDict */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="manufacturers-dict-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'word')->textInput(['maxlength' => true]) ?>


    <?= $form->field($model, 'manufacturers_id')->dropDownList(\app\models\Manufacturers::fetchNames()) ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p>Нет нужного производителя в списке? - нажимайте
        <?php

        Modal::begin([
            'title' => '<h2>Добавление производителя</h2>',
			'size' => Modal::SIZE_LARGE,
            'toggleButton' => [
                'label' => 'Создать нового производителя',
                'tag' => 'button',
                'class' => 'btn btn-success',
            ],
            //'footer' => 'Низ окна',
        ]);
        $manufacturerModel=new \app\models\Manufacturers();
        $manufacturerModel->name=$model->word;
        $manufacturerModel->full_name=$model->word;

        echo $this->render(
            '/manufacturers/_form',
            [
                'model'=>$manufacturerModel,
            ]
        );
        $js = <<<JS
    $('#manufacturers-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        $.ajax({
            url: '/web/manufacturers/create',
            type: 'POST',
            data: data,
            success: function(res){
                //alert();
                window.location.replace(window.location.toString()+'&manufacturers_id='+res[0].id);
            },
            error: function(){
                alert('Error!');
            }
        });
        return false;
    });
JS;
        $this->registerJs($js);
        Modal::end(); ?>
    </p>

</div>
