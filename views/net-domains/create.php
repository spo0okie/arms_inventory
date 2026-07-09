<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$this->title = "Новый ".\app\models\NetDomains::$title;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="net-domains-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
