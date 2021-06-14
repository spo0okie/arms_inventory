<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

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
?>

<h2>Расписание на ближайшие 7 дней</h2>
<p>С учетом праздничных дней</p>
<?= DetailView::widget(['model'=>$model,'attributes'=>$dateAttr]) ?>
