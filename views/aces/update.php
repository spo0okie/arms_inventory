<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$this->title = 'Правка: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => \app\models\Aces::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="aces-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
