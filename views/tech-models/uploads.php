<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$this->title = 'Вложения: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Вложения';
?>
<div class="tech-models-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('/scans/_form', [
        'model' => $model,
		'link' => 'tech_models_id',
    ]) ?>

</div>
