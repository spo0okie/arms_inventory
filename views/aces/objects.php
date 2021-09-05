<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($glue)) $glue=' <br /> ';
$items=[];


foreach ($model->users as $user)
	$items[]=$this->render('/users/item',['model'=>$user,'static_view'=>true]);

foreach ($model->comps as $comp)
	$items[]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true]);

foreach ($model->netIps as $ip)
	$items[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);

if (!count($items))
	$items[]='- не задано -';

echo implode($glue,$items);