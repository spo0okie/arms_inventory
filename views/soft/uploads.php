<?php

use app\models\Soft;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$this->title = 'Изображения: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Soft::$title, 'url' => ['/soft/index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изображения';
?>
<div class="tech-models-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('/scans/_form', [
        'model' => $model,
		'link' => 'soft_id',
    ]) ?>

</div>
