<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

?>
<div class="schedules-ttip ttip-card">
	<?= $this->render('week',['model'=>$model])?>
	<?= $this->render('7days',['model'=>$model])?>
</div>
