<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\SoftSearch */
/* @var $form yii\widgets\ActiveForm */
//$items=\yii\helpers\ArrayHelper::map(\app\models\Soft::fetchItems($items),'id','descr');
asort($items);
?>

<div class="soft-search">

    <?php $form = ActiveForm::begin([
        'action' => ['soft/update','return'=>'previous','items'=>$addItems],
        'method' => 'get',
    ]); ?>


    <?= \kartik\select2\Select2::widget([
        'name'=>'id',
        'data'=>$items
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
