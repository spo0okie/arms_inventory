<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

$this->title = "Новый ".app\models\SoftLists::$title;
$this->params['breadcrumbs'][] = ['label' => app\models\SoftLists::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-lists-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
