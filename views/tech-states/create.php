<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\TechStates */

$this->title = 'Новое состояние';
$this->params['breadcrumbs'][] = ['label' => \app\models\TechStates::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-states-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
