<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

$this->title = 'Правка: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\Techs::$title, 'url' => ['/techs/index']];
$this->params['breadcrumbs'][] = ['label' => $model->tech->num, 'url' => ['/techs/view','id'=>$model->techs_id]];
$this->params['breadcrumbs'][] = ['label' => \app\models\Ports::$port_prefix.$model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="ports-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
