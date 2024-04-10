<?php
/*
 * Задача: Посмотреть расписание на следующие 7 дней (включая текущий)
 * Если за эти 7 дней есть
 *  - Дни-исключения
 *  - Периоды работоспособности/неработоспособности
 *  - Переход с одного недельного графика на другой
 * То вывести фактическое расписание на каждый из 7 дней
 */

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */


use app\models\Schedules;

if (!isset($days_forward)) $days_forward=10;

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
	$week=$model->getWeekSchedule($dateDay);
	if (is_object($week)) $weeks[$week->id]=$week; else $week[null]=null;
}

if (
	(is_array($exceptions) && count($exceptions))
||
	(is_array($periods) && count($periods))
||
	(count($weeks)>1)
) {
	?>

<div class="schedule-next-days-modeling mb-4">
<h2>В ближайшие <?= $days_forward ?> дней есть изменения в расписании</h2>
<p>
	праздничные дни/ аварийные простои / смена недельного графика работы и т.п.<br>
	Посмотрите внимательно график на ближайшие <?= $days_forward ?> дней
</p>
	<table class="table table-condensed table-hover table-borderless">
		<?php for ($i=0; $i<$days_forward; $i++) {
			$date=date('Y-m-d',time()+86400*$i);
			$day=$model->getDateSchedule($date);
			if (is_object($day)) {
				$comment=$day->comment;
				$objDay=$day;
			} elseif (is_array($day)) {
				$tokens=[];
				$source=[];
				//если есть расписание
				if (isset($day['sources']['master']) && is_object($master=$day['sources']['master'])) {
					/** @var Schedules $master */
					if ($master->id==$model->id) {    //если это тоже самое
						$tokens[] = 'Основное';        //говорим что основное
					} elseif ($master->isOverride) {    //если это период с другим расписанием
						if (strlen(trim($master->description)))
							$tokens[] = $master->description;    //выводим пояснение к периоду
					} else							//если другое расписание
						$tokens[]=$master->name;	//его имя
				}
				//если есть день недели
				if (strlen(trim($day['day']->comment)))
					$tokens[]=$day['day']->comment;
				
				if (count($tokens)) $source[]=implode(' ',$tokens);

				//если есть наложения
				if (isset($day['sources']['periods']) && count($day['sources']['periods']))
					$source[]='наложения';
				$comment=implode(' + ',$source);
				$objDay=$day['day'];
			} else {
				$comment='';
				$objDay=null;
			}
		?>
			<tr>
				<td>
					<span class="text-nowrap"><?= Yii::$app->formatter->asDate(time()+86400*$i,'dd.MM.yyyy (E)') ?></span>
				</td>
				<td>
					<?= $this->render('/schedules-entries/item',['model'=>$day,'date'=>$date]) ?>
				</td>
				<td>
					<?= $this->render('/schedules-entries/item',['model'=>$day,'name'=>$comment,'date'=>$date]) ?>
				</td>
				<td width="33%">
					<?= $this->render('/schedules-entries/stripe',['model'=>$objDay,'schedule'=>$model]) ?>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
<?php }