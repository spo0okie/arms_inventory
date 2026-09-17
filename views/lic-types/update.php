<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

$this->title = 'Редактирование: '.$model->descr;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="lic-types-update">

    <h1><?= Html::encode($this->title) ?><?= \app\components\CopyObjectWidget::widget(['model'=>$model]) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
