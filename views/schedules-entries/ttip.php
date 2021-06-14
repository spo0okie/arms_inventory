<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

$name=$model->date==='def'?
	('График по умолчанию (на каждый день)')
	:
	('График на '.$model->day);

?>
<div class="schedules-entries-ttip ttip-card">
    <h1><?= Html::encode($name) ?> : <?= $model->description ?></h1>
	<h4><?= $model->comment ?></h4>
	<p><?= $model->history ?></p>
	<hr />
	<b>Расписание</b>:	<?= $this->render('/schedules/item',['model'=>$model->master,'static_view'=>true]) ?>
	<br />
	<br />
	Если кликнуть - откроется нужное расписание, и этот день будет подсвечен
	
</div>
