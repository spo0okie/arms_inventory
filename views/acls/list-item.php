<?php


/* @var $this yii\web\View */
/* @var $model app\models\Acls */

use app\components\ItemObjectWidget;
use app\components\widgets\page\ModelWidget;

$header=is_object($model->schedule)?
	ModelWidget::widget(['model'=>$model->schedule, 'options'=>['static_view'=>true]]).':'
	:
	'Доступ к '. ModelWidget::widget(['model'=>$model]).':';

$content="<h5>$header</h5>";
		
$items=[];
foreach ($model->aces as $ace)
	$items[]=$this->render('/aces/objects',['model'=>$ace,'glue'=>'']);
	
$content.='<div class="px-1">'.implode(' ',$items).'</div>';

if (is_object($model->schedule)) {
	$inactive=!$model->schedule->isWorkTime(date('Y-m-d'),date('H:i:s'));
} else $inactive=false;

echo ItemObjectWidget::widget([
	'model'=>$model,
	'link'=>$content,
	'archived'=>$inactive
]);

