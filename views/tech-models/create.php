<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$this->title = 'Новая модель';
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-models-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
