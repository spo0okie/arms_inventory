<?php

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

use app\components\HistoryRecordWidget;

?>
<div class="schedules-ttip ttip-card">
	<?= HistoryRecordWidget::widget(compact('model')) ?>
	<?= $this->render('week-description',['model'=>$model])?>
	<?= $this->render('7days',['model'=>$model])?>
	<?= $this->render('services',['model'=>$model])?>
</div>
