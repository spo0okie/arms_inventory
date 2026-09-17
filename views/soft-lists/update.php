<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

$this->title = 'Изменение списка: '.$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'Списки ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->descr, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменеие';
?>
<div class="soft-lists-update">

    <h1><?= Html::encode($this->title) ?><?= \app\components\CopyObjectWidget::widget(['model'=>$model]) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
