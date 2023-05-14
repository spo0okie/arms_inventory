<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

$this->title = 'Create Attaches';
$this->params['breadcrumbs'][] = ['label' => 'Attaches', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="attaches-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
