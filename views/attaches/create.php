<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

$this->title = 'Create Attaches';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="attaches-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
