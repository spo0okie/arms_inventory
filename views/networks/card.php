<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$deleteable=count($model->ips); //тут переопределить возможность удаления элемента
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

<?php if (is_object($model->netVlan)) { ?>
	<h4>
		VLAN: <?= $this->render('/net-vlans/item',['model'=>$model->netVlan]) ?>
		<?php if (is_object($model->netVlan->segment)) { ?>
			→ Сегмент <?= $this->render('/segments/item',['model'=>$model->netVlan->segment]) ?>
		<?php } ?>
	</h4>
<?php } ?>

<?php if (is_object($model->netVlan)) { ?>
	<h4>L2 Домен: <?= $this->render('/net-domains/item',['model'=>$model->netDomain]) ?></h4>
<?php } ?>

	<br />
	<div class="row">
		<div class="col-md-6">
			<h4>Шлюз</h4>
			<?= $model->readableRouter; ?>
		</div>
		<div class="col-md-6">
			<h4>DHCP</h4>
			<?= $model->readableDhcp; ?>
		</div>
	</div>
	<br />
	<div class="row">
		<div class="col-md-12">
			<h4>Использовано:</h4>
			<?= $this->render('used',['model'=>$model]) ?>
		</div>
	</div>
<?php