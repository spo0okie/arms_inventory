<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

$this->title = 'Create Materials Usages';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="materials-usages-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
