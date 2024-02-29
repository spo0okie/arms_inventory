<?php
/**
 * Рендер элемента графика рабочего дня
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:33
 */

use app\models\HistoryModel;
use app\models\SchedulesEntries;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

if (is_object($model)) {
	if (!isset($name)) {
		if ($model->is_period) {
			$name=$model->periodSchedule;
		} else {
			$name=$model->mergedSchedule;
			$tokens=explode(',',$name);
			foreach ($tokens as $i=>$token)
				$tokens[$i]='<span class="text-nowrap">'.$token.'</span>';
			$name=implode(', ',$tokens);
			
		}
	}
	if (!isset($date))$date=$model->date;
	if ($model instanceof HistoryModel) {
		$ttipUrl=Url::to(['schedules-entries/ttip','id'=>$model->master_id,'timestamp'=>$model->updated_at]);
	} else {
		$ttipUrl=Url::to(['schedules-entries/ttip','id'=>$model->id]);
	}
?>

	<span class="schedules-entries-item"
		  qtip_ajxhrf="<?= $ttipUrl ?>"
	>
		<?= Html::a($name,[
			'/schedules/view/',
			'id'=>$model->schedule_id,
			'date'=>$date,
			'entry'=>$model->date,
			'#'=>'day-'.$model->date.'-'.$model->date_end]) ?>
	</span>
<?php } elseif (is_array($model)) {
	if (!isset($name)) $name= SchedulesEntries::scheduleWithoutMetadata($model['schedule']);
	if (!isset($date)) $date=$model['day']->date;
	if (count($model['posPeriods'])) {
		$positive=[];
		foreach ($model['posPeriods'] as $period)
			$positive[]=$period->id;
	} else $positive=null;
	
	if (count($model['negPeriods'])) {
		$negative=[];
		foreach ($model['negPeriods'] as $period)
			$negative[]=$period->id;
	} else $negative=null;

	?>
	<span class="schedules-entries-item"
		  qtip_ajxhrf="<?= Url::to(['schedules-entries/ttip','id'=>$model['day']->id,'positive'=>$positive,'negative'=>$negative]) ?>"
	>
		<?= Html::a($name,[
			'/schedules/view/',
			'id'=>$model['day']->schedule_id,
			'date'=>$date,
			'entry'=>$model['day']->date,
			'#'=>'day-'.$model['day']->date,
			'positive'=>$positive,
			'negative'=>$negative
		]) ?>
	</span>
	
<?php } else
    echo ' - график не определен -';

