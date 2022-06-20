<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */


if (!isset($days_forward)) $days_forward=7;

if (!isset($static_view)) $static_view=false;

$today=strtotime(date('Y-m-d 00:00:00+0000'));
$periodEnd=$today+($days_forward+1)*60*60*24-1;
//var_dump($today);

$exceptions=$model->findExceptions($today,$periodEnd);
$periods=$model->findPeriods($today,$periodEnd);
$weeks=[];
$dateAttr=[];
for ($i=0; $i<$days_forward; $i++) {
	$dateDay=gmdate('Y-m-d',$today+86400*$i+Yii::$app->params['schedulesTZShift']);
	$dateLabel='График на '.Yii::$app->formatter->asDate(time()+86400*$i,'full');
	$dateAttr[]=[
		'label' => $dateLabel,
		'format' => 'raw',
		'value'=> $this->render('/schedules-entries/item',[
			'model'=>$model->getDateScheduleRecursive($dateDay,null)
		])
	];
	$week=$model->findEffectiveWeekSchedule($dateDay);
	$weeks[$week->id]=$week;
}

if (
	(is_array($exceptions) && count($exceptions))
||
	(is_array($periods) && count($periods))
||
	(count($weeks)>1)
) {
	?>

<div class="schedule-next-days-modeling">
<h2>В ближайшие <?= $days_forward ?> дней есть исключения</h2>
<p>праздничные дни/ аварийные простои и т.п.<br> Посмотрите внимательно график на ближайшие <?= $days_forward ?> дней</p>
	<table class="table table-condensed table-hover table-borderless">
		<?php for ($i=0; $i<$days_forward; $i++) {
			$day=$model->getDateSchedule(date('Y-m-d',time()+86400*$i));
			if (is_object($day)) {
				$comment=$day->comment;
			} elseif (is_array($day)) {
				$comment=$day['day']->comment.' + наложения';
			} else $comment='';
		?>
			<tr>
				<td>
					<span class="text-nowrap"><?= Yii::$app->formatter->asDate(time()+86400*$i,'dd.MM.yyyy (E)') ?></span>
				</td>
				<td>
					<?= $this->render('/schedules-entries/item',['model'=>$day]) ?>
				</td>
				<td>
					<?= $this->render('/schedules-entries/item',['model'=>$day,'name'=>$comment]) ?>
				</td>
				<td width="33%">
					<?= $this->render('/schedules-entries/stripe',['model'=>$day,'schedule'=>$model]) ?>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
<?php }