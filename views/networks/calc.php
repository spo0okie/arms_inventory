<?php


/* @var $this yii\web\View */
/* @var $model app\models\Networks */

use app\components\AttributeHintWidget;

$deleteable=count($model->ips); //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

	<table class="table table-bordered table-striped table-sm table-hover">
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
				<td><?= AttributeHintWidget::widget([
						'model'=>$model,
						'attribute'=>$attr,
					]) ?></td>
				<td><?= $model->$attr ?></td>
			</tr>
		<?php } ?>
	</table>
