<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\ManufacturersDict */

$this->title = 'Добавить написание производителя';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="manufacturers-dict-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
