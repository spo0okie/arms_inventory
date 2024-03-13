<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

$this->title = 'Редактирование: '.$model->name;

include 'breadcrumbs.php';
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="places-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
