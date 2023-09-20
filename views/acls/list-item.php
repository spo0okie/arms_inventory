<?php


/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$header=is_object($model->schedule)?
	$this->render('/scheduled-access/item',['model'=>$model->schedule,'static_view'=>true]).':'
	:
	'Доступ к '.$this->render('/acls/item',['model'=>$model]).':';

$content="<h5>$header</h5>";
		
$items=[];
foreach ($model->aces as $ace)
	$items[]=$this->render('/aces/objects',['model'=>$ace,'glue'=>'']);
	
$content.='<div class="px-1">'.implode(' ',$items).'</div>';

if (is_object($model->schedule)) {
	$inactive=!$model->schedule->isWorkTime(date('Y-m-d'),date('H:i:s'));
} else $inactive=false;

echo \app\components\ItemObjectWidget::widget([
	'model'=>$model,
	'link'=>$content,
	'archived'=>$inactive
]);