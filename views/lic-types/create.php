<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

$this->title = 'Добавление схемы лицензирования';
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="lic-types-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
