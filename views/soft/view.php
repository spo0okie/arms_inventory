<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

$this->title = $model->manufacturer->name.' '.$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
