<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
?>
<div class="tech-models-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
</div>