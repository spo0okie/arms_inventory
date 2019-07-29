<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */

$this->title = 'Новый тип';
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-types-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
