<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

$this->title = $model->to;
//крошки собираются автоматически в layout (views/layouts/main.php)
\yii\web\YiiAsset::register($this);
?>
<div class="materials-usages-view">
    <?= $this->render('card',['model'=>$model]) ?>