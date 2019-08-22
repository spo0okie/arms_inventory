<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

$this->title = 'Изм: ' . $model->to;
$this->params['breadcrumbs'][] = ['label' => \app\models\MaterialsUsages::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->to, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="materials-usages-update">

    <h2><?= Html::encode($this->title) ?></h2>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
