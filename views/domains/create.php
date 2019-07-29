<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Domains */

$this->title = 'Добавление домена';
$this->params['breadcrumbs'][] = ['label' => 'Домены AD', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
