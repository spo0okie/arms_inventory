<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

$weekAttr=[];

for ($i=1; $i<=7; $i++) {
	$weekLabel=\app\models\SchedulesEntries::$days[$i];
	$weekAttr[]=[
		'label' => $weekLabel,
		'format' => 'raw',
		'value'=> $this->render('/schedules-entries/item',[
			'model'=>$model->getWeekDayScheduleRecursive($i)
		])
	];
}
if (!isset($static_view)) $static_view=false;

$providingServices=$model->providingServices;
$supportServices=$model->supportServices;

$deleteable=!count($providingServices) && !count($supportServices);

?>
<h1>
	<?= $static_view?Html::a($model->name,['comps/view','id'=>$model->id]):$model->name ?>
	
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil" title="Изменить"></span>',['schedules/update','id'=>$model->id])) ?>
	
	<?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash" title="Удалить"/>', ['schedules/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить это расписание? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="glyphicon glyphicon-lock" title="Невозможно в данный момент удалить это расписание, удалить можно только пустое расписание не привязанное ни к каким объектам."></span>
		</span>
	<?php } ?>
	&nbsp;
</h1>


<p><?= $model->description ?></p>

<h2><?= implode('<br/>',$model->weekWorkTime) ?></h2>

