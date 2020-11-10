<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

?>
<div class="soft-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'hitlist'=>$hitlist,'static_view'=>false]) ?>
</div>
