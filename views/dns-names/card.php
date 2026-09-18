<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/**
 * Карточка DNS-имени для view и tooltip.
 *
 * @var yii\web\View $this
 * @var app\models\DnsNames $model
 * @var bool $static_view
 */

if (!isset($static_view)) $static_view = false;
?>

<div class="dns-names-card">

	<h1 class="text-monospace">
		<?= LinkObjectWidget::widget([
			'model' => $model,
			'static' => $static_view,
			'confirmMessage' => 'Действительно удалить это DNS-имя?',
		]) ?>
	</h1>

	<?php
	$rows = [];
	foreach (['domain_id', 'host', 'comment'] as $field) {
		$rows[] = ModelFieldWidget::renderFieldRow(
			$model,
			$field,
			['item_options' => ['static_view' => $static_view]]
		);
	}
	echo Html::tag('p', implode('<br />', array_filter($rows)));

	//адреса с узлами, к которым они привязаны: к чему относится имя, выводится
	//именно отсюда (собственных ссылок на узлы и сервисы у имени нет)
	$ips = [];
	foreach ($model->netIps as $ip) {
		$nodes = [];
		foreach ($ip->comps as $comp) $nodes[] = ModelWidget::widget(['model' => $comp, 'options' => ['static_view' => $static_view]]);
		foreach ($ip->techs as $tech) $nodes[] = ModelWidget::widget(['model' => $tech, 'options' => ['static_view' => $static_view]]);
		$ips[] = ModelWidget::widget(['model' => $ip, 'options' => ['static_view' => $static_view]])
			. (count($nodes) ? ' <span class="opacity-75">&rarr;</span> ' . implode(', ', $nodes) : '');
	}
	if (count($ips)) {
		echo Html::tag('h4', ModelFieldWidget::renderFieldTitle($model, 'netIps'));
		echo Html::tag('p', implode('<br />', $ips));
	} else {
		echo Html::tag('p', ModelFieldWidget::renderFieldTitle($model, 'netIps') . ': '
			. Html::tag('span', 'ни на что не указывает', ['class' => 'opacity-75']));
	}

	//цепочка имя → адрес → проброс → узел: куда на самом деле ведёт имя
	echo $this->render('/aces/forwards', [
		'models' => $model->forwardsOut, 'owner' => $model, 'attribute' => 'forwardsOut', 'static_view' => $static_view,
	]);
	?>

</div>
