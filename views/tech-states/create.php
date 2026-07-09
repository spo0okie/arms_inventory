<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\TechStates */

$this->title = 'Новое состояние';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="tech-states-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
