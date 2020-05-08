<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

$this->title = 'Новый сегмент';
$this->params['breadcrumbs'][] = ['label' => \app\models\Segments::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="segments-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
