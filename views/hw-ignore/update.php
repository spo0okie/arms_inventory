<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */

$this->title = 'Update Hw Ignore: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Hw Ignores', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="hw-ignore-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
