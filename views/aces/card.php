<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$items=[];

foreach ($model->users as $user)
	$items[]=$this->render('/users/item',['model'=>$user,'static_view'=>true,'icon'=>true,'short'=>true]);

foreach ($model->comps as $comp)
	$items[]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true]);

foreach ($model->netIps as $ip)
	$items[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true]);

if (!count($items))
	$items[]='- не задано -';


$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]=\app\models\Aces::$noAccessName;
?>

<!--
<h1>
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['aces/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['aces/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?php
/*
echo '<h3>Объекты</h3>';
foreach ($model->users as $user) {
	echo $this->render('/users/item',['model'=>$user,'static_view'=>true]).'<br />';
}

foreach ($model->comps as $comp) {
	echo $this->render('/comps/item',['model'=>$comp,'static_view'=>true]).'<br />';
}

foreach ($model->netIps as $ip) {
	echo $this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]).'<br />';
}


echo '<h3>Доступ</h3>';
foreach ($model->accessTypes as $accessType) {
	echo $this->render('/access-types/item',['model'=>$accessType,'static_view'=>true]).'<br />';
}*/
?>
<table class="acls-table">
	<tr>
		<?= $this->render('tdrow',['model'=>$model,'static_view'=>$static_view]) ?>
	</tr>
</table>
-->

<div class="card w-100 my-2">
	<div class="row g-0">
		<div class="col-md-9 bg-light bg-opacity-50 rounded-left p-2">
			<?= implode(' <br /> ',$items) ?>
		</div>
		<div class="col-md-3 bg-secondary rounded-right p-2">
			<?= $this->render('item',['model'=>$model,'name'=>implode(', ',$accessTypes),'show_delete'=>!$static_view]) ?>
		</div>
	</div>

</div>
