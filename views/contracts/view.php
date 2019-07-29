<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Contracts::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="contracts-view">
    <?= $this->render('card',['model'=>$model]) ?>
</div>
