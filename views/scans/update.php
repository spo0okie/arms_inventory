<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */

$this->title = 'Редактирование: '. $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\Scans::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->descr, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="scans-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
