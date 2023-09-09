<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$this->title = 'Правка: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\NetDomains::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="net-domains-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
