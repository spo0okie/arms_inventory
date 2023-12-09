<?php

use app\components\ExpandableCardWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=$model->isNewRecord;
$renderer=$this;
?>
<h2>Расписание на неделю</h2>
<div class="mb-3">

<?php
$content=$this->render('item',['model'=>$model]);


$periods=$model->overrides;
if (count($periods)) {
	
	foreach ($periods as $period) {
		$content.=$this->render('item',['model'=>$period]);
	}
	
}
echo ExpandableCardWidget::widget([
	'content'=>$content,
	'maxHeight'=>650,
	'switchOnlyOnButton'=>true
]);
?>
</div>
