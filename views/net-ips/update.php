<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$this->title = 'Правка: ' . $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\NetIps::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="net-ips-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
