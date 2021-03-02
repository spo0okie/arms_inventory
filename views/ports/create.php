<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

$this->title = "Новый ".app\models\Ports::$title;
$this->params['breadcrumbs'][] = ['label' => app\models\Ports::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ports-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
