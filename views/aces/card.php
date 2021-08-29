<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['aces/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['aces/delete', 'id' => $model->id], [
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
