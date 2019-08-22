<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */

$this->title = \app\models\MaterialsTypes::$title.': ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\MaterialsTypes::$title, 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => , 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование '.$model->name;
?>
<div class="materials-types-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
