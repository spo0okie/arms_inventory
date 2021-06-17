<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */


if (!isset($days_forward)) $days_forward=7;

if (!isset($static_view)) $static_view=false;

$today=strtotime(date('Y-m-d 00:00:00'));
$periodEnd=$today+($days_forward+1)*60*60*24-1;

$exceptions=$model->findExceptions($today,$periodEnd);

$dateAttr=[];
for ($i=0; $i<7; $i++) {
	$dateDay=date('Y-m-d',time()+86400*$i);
	$dateLabel='График на '.Yii::$app->formatter->asDate(time()+86400*$i,'full');
	$dateAttr[]=[
		'label' => $dateLabel,
		'format' => 'raw',
		'value'=> $this->render('/schedules-entries/item',[
			'model'=>$model->getDateScheduleRecursive($dateDay)
		])
	];
}

if (is_array($exceptions) && count($exceptions)) {
	?>


<h2>В ближайшие <?= $days_forward ?> дней есть исключения</h2>
<p>праздничные дни/ аварийные простои и т.п.<br> Посмотрите внимательно график на ближайшие <?= $days_forward ?> дней</p>
<?= DetailView::widget(['model'=>$model,'attributes'=>$dateAttr]) ?>

<?php }