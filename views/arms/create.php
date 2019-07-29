<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Arms */

$this->title = 'Добавление АРМ';
$this->params['breadcrumbs'][] = ['label' => 'АРМы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="arms-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
