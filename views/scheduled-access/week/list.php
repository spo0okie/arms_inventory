<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;

echo '<h2>Расписание на неделю</h2>';
echo $this->render('item',['model'=>$model]);

$periods=$model->overrides;
if (count($periods)) {
	
	foreach ($periods as $period) {
		echo $this->render('item',['model'=>$period]);
	}
	
}
