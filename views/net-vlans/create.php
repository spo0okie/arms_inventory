<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

$this->title = "Новый ".\app\models\NetVlans::$title;
$this->params['breadcrumbs'][] = ['label' => \app\models\NetVlans::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="net-vlans-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
