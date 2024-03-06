<?php

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

use app\components\IsHistoryObjectWidget;

?>
<div class="schedules-ttip ttip-card">
	<?= IsHistoryObjectWidget::widget(compact('model')) ?>
	<h3><?= $this->render('week-description',['model'=>$model])?></h3>
	<?= $this->render('7days',['model'=>$model])?>
	<?= $this->render('services',['model'=>$model])?>
</div>
