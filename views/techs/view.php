<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

$this->title = $model->num;
$this->params['breadcrumbs'][] = ['label' => 'Techs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="techs-view">
    <?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
</div>
