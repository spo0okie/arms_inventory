<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $hitlist string */

?>
<div class="soft-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'hitlist'=>$hitlist,'static_view'=>true]) ?>
</div>
