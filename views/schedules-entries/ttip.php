<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

if ($model->is_period) {
	$name=$model->getPeriodSchedule();
} else {
	$name=$model->date==='def'?
		('График по умолчанию (на каждый день)')
		:
		('График на '.$model->day);
	
}

//var_dump($positive);
?>
<div class="schedules-entries-ttip ttip-card">
    <h1><?= Html::encode($name) ?> : <?= $model->comment ?></h1>
	<b>
	<?php if ($model->is_period) {
		echo ($model->is_work?'Рабочий период':'Нерабочий период').' из расписания';
	} else echo 'Из расписания'; ?>
	</b>:
	<?= $this->render('/schedules/item',['model'=>$model->master,'static_view'=>true]) ?>
	<p><?= $model->history ?></p>
	<br />
	<?php if (isset($positive) && is_array($positive) && count($positive)) {
		echo '<h3>Наложившиеся на этот день доп. рабочие периоды:</h3>';
	
		foreach ($positive as $pos) {
			echo $this->render('/schedules-entries/item',['model'=>\app\models\SchedulesEntries::findOne($pos)]).'<br />';
		}
		
		echo '<br />';
	
	} ?>
	<?php if (isset($negative) && is_array($negative) && count($negative)) {
		echo '<h3>Наложившиеся на этот день доп. нерабочие периоды:</h3>';
		
		foreach ($negative as $neg) {
			echo $this->render('/schedules-entries/item',['model'=>\app\models\SchedulesEntries::findOne($neg)]).'<br />';
		}
		
		echo '<br />';
		
	} ?>
	<br />
	<hr />
	<br />
	Если кликнуть - откроется нужное расписание
	<ul>
		<li>этот день будет подсвечен зеленым</li>
		<li>наложившиеся рабочие периоды - синим</li>
		<li>наложившиеся нерабочие периоды - красным</li>
	</ul>
</div>
