<?php

use app\components\assets\IntegrationCellsAsset;
use app\components\integrations\IntegrationsRegistry;
use app\components\ModelFieldWidget;
use app\components\UrlParamSwitcherWidget;
use app\models\NetIps;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$showEmpty= Yii::$app->request->get('showEmpty',false);
$ipModel=new NetIps(); //для подписей колонок (label + «?» с hint атрибута)
//колонки интеграций (статус VPN): персонализации у этой таблицы нет, поэтому
//колонка видна, только если интеграция применима хотя бы к одному адресу сети
$integrationColumns=IntegrationsRegistry::tableColumns(NetIps::class,$model->ipsByAddr);
if ($integrationColumns) IntegrationCellsAsset::register($this);
?>
<table class="table table-bordered table-sm table-hover net-ips">
	<tr>
		<th>
			№
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'text_addr',null,'span') ?>
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'name',null,'span') ?>
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'comment',null,'span') ?>
			<?= UrlParamSwitcherWidget::widget([
				'cssClass'=>'float-end',
				'param'=>'showEmpty',
				'hintOff'=>'Скрыть не занятые IP',
				'hintOn'=>'Показать не занятые IP',
				'label'=>'Пустые',
				'reload'=>false,
				'scriptOn'=>"\$('.empty-item').show();",
				'scriptOff'=>"\$('.empty-item').hide();",
			]) ?>

		</th>
		<?php foreach ($integrationColumns as $column) { ?>
			<th<?= $column['hint']?' qtip_ttip="'.\yii\helpers\Html::encode($column['hint']).'"':'' ?>>
				<?= \yii\helpers\Html::encode($column['label']) ?>
			</th>
		<?php } ?>
	</tr>
	<?php
		for ($i=0; $i<$model->capacity; $i++) {
			$addr=$model->addr+$i;
			echo $this->render('ip-row',[
				'model'=>$model,
				'i'=>$i,
				'showEmpty'=>$showEmpty,
				'integrationColumns'=>$integrationColumns,
			]);
	} ?>
</table>