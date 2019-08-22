<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

$this->title = 'Правка: ' . $model->sname;
$this->params['breadcrumbs'][] = ['label' => \app\models\Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="materials-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
