<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($glue)) $glue=' <br /> ';
if (!isset($empty)) $empty='- не задано -';
$items=[];


foreach ($model->users as $user)
	$items[]=$this->render('/users/item',['model'=>$user,'static_view'=>true,'icon'=>true]);

foreach ($model->comps as $comp)
	$items[]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true]);

foreach ($model->netIps as $ip)
	$items[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true]);

if (!count($items) && $empty)
	$items[]=$empty;

echo implode($glue,$items);