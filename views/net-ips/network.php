<?php

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;

//компактная сводка сети адреса: только то, что касается самого адреса
//(занятость/DHCP сети — на странице сети, не здесь)
$network=$model->network;
if (!is_object($network)) return;
?>
<div class="net-ips-network">
	<h4 class="mb-1">
		<?= ModelFieldWidget::renderFieldTitle($model,'network',null,'span') ?>:
		<?= ModelWidget::widget(['model'=>$network]) ?>
	</h4>
	<?php if ($network->comment) { ?>
		<div class="mb-1"><?= ModelFieldWidget::renderFieldValue($network,'comment') ?></div>
	<?php } ?>
	<?php if (is_object($network->segment)) { ?>
		<div>Сегмент: <?= ModelWidget::widget(['model'=>$network->segment]) ?></div>
	<?php } ?>
	<?php if (is_object($network->netVlan)) { ?>
		<div>VLAN: <?= ModelWidget::widget(['model'=>$network->netVlan]) ?>
			<?php if (is_object($network->netDomain)) { ?>
				// L2: <?= ModelWidget::widget(['model'=>$network->netDomain]) ?>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ($network->readableRouter) { ?>
		<div><?= ModelFieldWidget::renderFieldTitle($network,'readableRouter',null,'span') ?>:
			<?= ModelFieldWidget::renderFieldValue($network,'readableRouter') ?></div>
	<?php } ?>
</div>
