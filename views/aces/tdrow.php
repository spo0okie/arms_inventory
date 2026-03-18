<?php

use app\components\widgets\page\ModelWidget;
use app\models\Aces;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$items=[];

foreach ($model->users as $user)
	$items[]= ModelWidget::widget(['model'=>$user,'options'=>['static_view'=>true]]);

foreach ($model->comps as $comp)
	$items[]= ModelWidget::widget(['model'=>$comp,'options'=>['static_view'=>true]]);

foreach ($model->netIps as $ip)
	$items[]= ModelWidget::widget(['model'=>$ip,'options'=>['static_view'=>true]]);

if (!count($items))
	$items[]='- не задано -';


$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]= Aces::$noAccessName;
?>

<td class="ACE access">
	<?= $this->render('item',['model'=>$model,'name'=>implode(', ',$accessTypes),'show_delete'=>!$static_view]) ?>
</td>

<td class="ACE objects">
	<?= implode(' <br /> ',$items) ?>
</td>






