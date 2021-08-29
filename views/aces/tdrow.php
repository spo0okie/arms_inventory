<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$items=[];

foreach ($model->users as $user)
	$items[]=$this->render('/users/item',['model'=>$user,'static_view'=>true]);

foreach ($model->comps as $comp)
	$items[]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true]);

foreach ($model->netIps as $ip)
	$items[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);

if (!count($items))
	$items[]='- не задано -';


$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]=\app\models\Aces::$noAccessName;
?>

<td class="ACE access">
	<?= $this->render('item',['model'=>$model,'name'=>implode(', ',$accessTypes),'show_delete'=>!$static_view]) ?>
</td>

<td class="ACE objects">
	<?= implode(' <br /> ',$items) ?>
</td>





