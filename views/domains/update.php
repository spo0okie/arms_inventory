<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Domains */

$this->title = 'Редактирование домена: '.$model->name;
$this->params['breadcrumbs'][] = ['label' => 'Домены AD', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
