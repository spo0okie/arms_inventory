<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */

$this->title = 'Новая категория материалов';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="materials-types-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
