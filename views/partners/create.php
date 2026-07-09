<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Partners */

$this->title = 'Новый контрагент';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="partners-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
