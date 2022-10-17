<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

$this->title = 'Правка услуги телефонии: ' . $model->sname;
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgPhones::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="org-phones-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
