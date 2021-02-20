<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

$this->title = 'Правка: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\NetVlans::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="net-vlans-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
