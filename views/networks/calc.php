<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$deleteable=count($model->ips); //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h2>Калькулятор:</h2>
	<table class="table table-bordered table-striped">
		<tr>
			<th>Имя</th>
			<th>Значение</th>
		</tr>
		<?php foreach ([
			'readableNetMask',
			'readableWildcard',
			'readableNetworkIp',
			'readableFirstIp',
			'readableLastIp',
			'readableBroadcastIp',
			'maxHosts',
	   ] as $attr) { ?>
			<tr>
				<td><?= \app\components\AttributeHintWidget::widget([
						'model'=>$model,
						'attribute'=>$attr,
					]) ?></td>
				<td><?= $model->$attr ?></td>
			</tr>
		<?php } ?>
	</table>
