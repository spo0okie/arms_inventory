<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Places */

$this->title = 'Добавить помещение';
$this->params['breadcrumbs'][] = ['label' => \app\models\Places::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="places-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
