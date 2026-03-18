<?php

use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($glue)) $glue=' <br /> ';
if (!isset($empty)) $empty='- не задано -';
$items=[];


foreach ($model->users as $user)
	$items[]= ModelWidget::widget(['model'=>$user,'options'=>['static_view'=>true,'icon'=>true]]);

foreach ($model->comps as $comp)
	$items[]= ModelWidget::widget(['model'=>$comp,'options'=>['static_view'=>true,'icon'=>true]]);

foreach ($model->netIps as $ip)
	$items[]= ModelWidget::widget(['model'=>$ip,'options'=>['static_view'=>true,'icon'=>true]]);

if (!count($items) && $empty)
	$items[]=$empty;

echo implode($glue,$items);

