<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

$deleteable=!count($model->childs);

$this->title =  $model->type->name.': '. $model->model;

$this->params['breadcrumbs'][] = ['label' => \app\models\Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>
<div class="materials-view">

	<?= $this->render('card',['model'=>$model]) ?>


</div>
