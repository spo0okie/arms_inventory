<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\ContractsStates */

$this->title = 'Новый статус';
$this->params['breadcrumbs'][] = ['label' => \app\models\ContractsStates::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-states-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
