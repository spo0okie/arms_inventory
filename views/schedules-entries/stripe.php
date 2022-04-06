<?php
/**
 * Рендер элемента графика рабочего дня
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:33
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

$bgClasses=[
	'',
	'bg-info',
	'bg-warning',
	'bg-danger'
];



if (
	is_object($model) &&
	$model->schedule!=='-' &&
	count($periods=$model->minuteIntervalsEx)
) {
	if (!isset($schedule)) $schedule=$model->master;
	$start=0;
	$end=1439;
	$bars=[];
	foreach ($periods as $period) {
		$hint=\app\models\SchedulesEntries::intMinutesToStrTimestamp($period[0]).
			'-'.
			\app\models\SchedulesEntries::intMinutesToStrTimestamp($period[1]);
		if ($period[0]-1>$start) { //период отодвинут от предыдущего, значит между ними промежуток
			$bars[]=['type'=>'off', 'size'=>((float)$period[0]-$start)/$end,'hint'=>$hint,];
		}
		if (!isset($schedule->metaClasses[$period['meta']]))
			$schedule->metaClasses[$period['meta']]=count($schedule->metaClasses);
		$bars[]=[
			'type'=>'on',
			'size'=>($period[1]-$period[0])/$end,
			'meta'=>$period['meta'],
			'class'=>$schedule->metaClasses[$period['meta']],
			'hint'=>$hint,
		];
		$start=$period[1]+1;
	}
	if ($end-1>$start) { //период отодвинут от конца дня, значит между ними промежуток
		$bars[]=['type'=>'off',	'size'=>((float)$end-$start)/$end,
			'hint'=>\app\models\SchedulesEntries::intMinutesToStrTimestamp($start).
			'-'.\app\models\SchedulesEntries::intMinutesToStrTimestamp($end)];
	}

	//var_dump($bars);
	
	echo '<div class="progress">';
	foreach ($bars as $bar) {
		$width=$bar['size']*100;
		if ($bar['type']=='off')
			echo '<div role="progressbar" style="width: '.$width.'%" aria-valuenow="'.$width.'" aria-valuemin="0" aria-valuemax="100" qtip_ttip="'.$bar['hint'].'"></div>';
		else {
			
			if ($bar['meta'] && ($meta=json_decode($bar['meta']))!==false) {
				if (isset($meta->user)) {
					if (is_object($user=\app\models\Users::findOne(['Login'=>$meta->user]))) {
						//var_dump($user);
						$bar['hint'].='<br>'.$this->render('/users/item',['model'=>$user,'short'=>true]);
					} else
						$bar['hint'].='<br>'.$meta->user;
				}
			}
			echo '<div class="progress-bar '.$bgClasses[$bar['class'] % count($bgClasses)].'" role="progressbar" style="width: '.$width.'%" aria-valuenow="'.$width.'" aria-valuemin="0" aria-valuemax="100" qtip_ttip=\''.$bar['hint'].'\'"></div>';
		}
	}
	
 
	echo '</div>';
	//var_dump($bars);
} else
    echo '<div class="progress" qtip_ttip="рабочие периоды отсутствуют"></div>';

