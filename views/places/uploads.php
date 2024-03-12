<?php

use app\models\TechTypes;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$this->title = 'Изображения: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => TechTypes::$title, 'url' => ['/places/index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изображения';
?>
<div class="tech-models-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('/scans/_form', [
        'model' => $model,
		'link' => 'places_id',
    ]) ?>

</div>
