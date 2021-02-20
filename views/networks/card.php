<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1 class="text-monospace">
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['networks/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['networks/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>
<?= Yii::$app->formatter->asNtext($model->comment) ?>

<?php if (!empty($model->vlan_id)) { ?>
	<h4>VLAN: <?= $this->render('/net-vlans/item',['model'=>$model->netVlan]) ?></h4>
<?php } ?>
<br />
<br />
<h4>Шлюз</h4>
<?= $model->readableRouter; ?>
<br />
<br />
<h4>DHCP</h4>
<?= $model->readableDhcp; ?>

