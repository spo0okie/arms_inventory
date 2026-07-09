<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

\yii\helpers\Url::remember();

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)

?>
<div class="contracts-view">
    <?= $this->render('card',['model'=>$model]) ?>
</div>
