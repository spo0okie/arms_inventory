<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';
?>
<div class="comps-ttip ttip-card">
	
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	
</div>