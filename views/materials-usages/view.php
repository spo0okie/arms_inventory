<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

$this->title = $model->to;
$this->params['breadcrumbs'][] = ['label' => \app\models\MaterialsUsages::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="materials-usages-view">
    <?= $this->render('card',['model'=>$model]) ?>