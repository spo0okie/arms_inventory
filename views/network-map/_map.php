<?php
/**
 * Тело карты сети: схема + списки. Общий для первой загрузки и для ответа
 * сверки с сетью (перерисовывается целиком со слоем находок).
 */

use app\components\widgets\page\ModelWidget;
use app\models\Ports;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $map \app\components\NetworkMap */
/* @var $site \app\models\Places */
/* @var $provider \app\components\integrations\providers\MacSearchProvider|null */
/* @var $scanStamp string|null отметка опроса, если карта со слоем сверки */

$overlay = $map->overlay;
$switches = [];
foreach ($map->nodes as $node) foreach ($node['members'] as $member) $switches[$member->id] = $member;

/** Коммутатор ссылкой */
$device = fn($tech) => ModelWidget::widget(['model' => $tech, 'options' => ['static_view' => true]]);

/** Порт на той стороне для записи: один - подставляем, неоднозначно - селект */
$peerPick = static function (array $resolved, string $field, int $index) {
	if (count($resolved['candidates'])) {
		$options = [];
		foreach ($resolved['candidates'] as $peer) {
			$options[] = Html::tag('option', Html::encode($peer['name']),
				['value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
		}
		return [Html::tag('select', implode('', $options), [
			'class' => 'form-select form-select-sm d-inline-block w-auto py-0 map-scan-peer',
			'data-field' => $field, 'data-finding' => $index,
			'qtip_ttip' => 'Какой это порт, по имени из LLDP однозначно не понять — выберите',
		]), []];
	}
	if (is_null($resolved['name'])) return ['', []];
	return [Ports::$port_prefix.Html::encode($resolved['name']).': ', [
		$field => $resolved['id'], $field.'_name' => $resolved['id'] ? '' : $resolved['name']]];
};
?>
<?php if (is_array($overlay)) { ?>
	<div class="text-secondary small mb-2">
		<?= Html::encode((string)$scanStamp) ?>;
		подтверждено: <?= count($overlay['confirmed']) ?>,
		не видно: <?= count($overlay['unseen']) ?>,
		найдено незаписанных: <?= count($overlay['found']) ?>,
		неопознанных соседей: <?= count($overlay['unknown']) ?><?php
		if (count($overlay['failed'])) { ?>, не ответили: <?= count($overlay['failed']) ?><?php } ?>
	</div>
<?php } ?>

<div class="border rounded p-2 mb-3" style="overflow:auto">
	<div id="network-map-diagram" class="mermaid"><?= Html::encode($map->mermaid()) ?></div>
</div>

<?php if (is_array($overlay) && count($overlay['found'])) { ?>
	<h4>Найдено, не записано</h4>
	<div class="text-secondary small mb-1">
		коммутаторы видят друг друга по LLDP/CDP, а связи в инвентаризации нет;
		🔗 записывает порт↔порт, «записать всё» — только однозначные
		<?= Html::button('Записать всё однозначное', ['class' => 'btn btn-sm btn-outline-success ms-2',
			'onclick' => 'mapScanAcceptAll()']) ?>
	</div>
	<table class="table table-sm table-striped w-auto">
		<tbody>
		<?php foreach ($overlay['found'] as $index => $found) {
			[$peerHtml, $fields] = $peerPick($found['peer'], 'peer', $index);
			$data = ['tech' => $found['a']->id, 'port' => $found['port'], 'do' => 'attach',
				'device' => $found['b']->id] + $fields;
			$unambiguous = !count($found['peer']['candidates']);
			$conflicts = array_filter([$found['conflict'], $found['conflict_remote'] ?? null]);
			?>
			<tr>
				<td><?= $device($found['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($found['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $peerHtml ?><?= $device($found['b']) ?>
					<?php if (is_null($found['peer']['name']) && !count($found['peer']['candidates'])) { ?>
						<span class="text-secondary small" qtip_ttip="<?= Html::encode(
							'Портов у модели не объявлено — связь запишется без порта. Сосед назвал порт: '
							.$found['lldp_port']) ?>">без порта</span>
					<?php } ?>
				</td>
				<td class="text-secondary small"><?= Html::encode($found['protocol']) ?></td>
				<td>
					<?php foreach ($conflicts as $conflict) { ?>
						<span class="text-warning small d-block" qtip_ttip="<?= Html::encode(
							'На одном из портов уже записано другое соединение — запись заменит его') ?>">
							вместо <?= $this->render('/ports/item', ['model' => $conflict,
								'include_tech' => true, 'reverse' => true]) ?></span>
					<?php } ?>
				</td>
				<td><?= Html::button('<i class="fas fa-link text-success"></i>', [
					'class' => 'btn btn-sm btn-link p-0'.($unambiguous && !count($conflicts) ? ' map-scan-accept' : ''),
					'qtip_ttip' => 'Записать связь '.$found['a']->name.' '.$found['port'].' ↔ '.$found['b']->name,
					'data-scan' => json_encode($data), 'data-finding' => $index,
					'onclick' => 'mapScanApply(this)',
				]) ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (count($map->edges)) { ?>
	<h4>Связи</h4>
	<table class="table table-sm table-striped w-auto">
		<thead><tr><th>Коммутатор</th><th>Порт</th><th></th><th>Порт</th><th>Коммутатор</th><th>Группа</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($map->edges as $key => $edge) { ?>
			<?php foreach ($edge['links'] as $link) { ?>
				<tr>
					<td><?= $device($link['port']->tech) ?></td>
					<td><?= $this->render('/ports/item', ['model' => $link['port']]) ?></td>
					<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
					<td><?= $this->render('/ports/item', ['model' => $link['peer']]) ?></td>
					<td><?= $device($link['peer']->tech) ?></td>
					<td class="text-secondary"><?= Html::encode($edge['aggr']) ?></td>
					<td><?php if (is_array($overlay)) {
						if (isset($overlay['confirmed'][$key])) { ?>
							<i class="fas fa-check text-secondary" qtip_ttip="Коммутаторы видят друг друга по LLDP/CDP"></i>
						<?php } elseif (isset($overlay['unseen'][$key])) { ?>
							<i class="fas fa-exclamation text-warning" qtip_ttip="<?= Html::encode(
								'Связь записана, но по LLDP/CDP её не видно ни с одной из ответивших сторон: '
								.'порт выключен, LLDP отключён или кабель переставили. Снимать связь по '
								.'одному этому признаку нельзя - проверьте в карточке коммутатора') ?>"></i>
						<?php } else { ?>
							<span class="text-secondary small" qtip_ttip="Ни одна из сторон не ответила на опрос">—</span>
						<?php } } ?></td>
				</tr>
			<?php } ?>
		<?php } ?>
		</tbody>
	</table>
<?php } else { ?>
	<div class="text-secondary mb-3">
		Связей между коммутаторами не записано. Они появляются отсюда («Сверить с
		сетью» → записать найденное), из карточек коммутаторов («Опросить порты»)
		либо руками в форме порта.
	</div>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['unknown'])) { ?>
	<h4>Неопознанные соседи</h4>
	<div class="text-secondary small mb-1">
		коммутатор видит соседа, а в инвентаризации такого нет (ни по адресу, ни по
		имени, ни по IP): незаписанное устройство — заведите его, и при следующей
		сверке связь предложится
	</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($overlay['unknown'] as $unknown) { $row = $unknown['row']; ?>
			<tr>
				<td><?= $device($unknown['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($unknown['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= Html::encode(trim((string)($row['remote_name'] ?? '')) ?: '?') ?>
					<?php if (!empty($row['remote_mac'])) { ?>
						<span class="mac_address small"><?= Html::encode(\app\models\Techs::formatMacs($row['remote_mac'])) ?></span>
					<?php } ?>
					<?php if (!empty($row['remote_port'])) { ?>
						<span class="text-secondary small">порт <?= Html::encode($row['remote_port']) ?></span>
					<?php } ?>
				</td>
				<td class="text-secondary small"><?= Html::encode($row['protocol'] ?? '') ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['outside'])) { ?>
	<h4>Соседи вне карты</h4>
	<div class="text-secondary small mb-1">опознаны, но это не коммутатор этой площадки</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($overlay['outside'] as $item) { ?>
			<tr>
				<td><?= $device($item['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($item['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $device($item['b']) ?> <span class="text-secondary small">порт
					<?= Html::encode($item['row']['remote_port'] ?? '') ?></span></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['failed'])) { ?>
	<h4>Не ответили</h4>
	<table class="table table-sm w-auto text-secondary">
		<tbody>
		<?php foreach ($overlay['failed'] as $failure) { ?>
			<tr>
				<td><?= isset($switches[$failure['target'] ?? 0]) ? $device($switches[$failure['target']])
					: Html::encode($failure['host'] ?? '') ?></td>
				<td<?= empty($failure['detail']) ? '' : ' qtip_ttip="'.Html::encode($failure['detail']).'"' ?>><?=
					Html::encode($failure['error'] ?? 'причина не указана') ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (count($map->outside)) { ?>
	<h4>Аплинки на другие площадки</h4>
	<div class="text-secondary small mb-1">
		та сторона — коммутатор другой площадки; на схеме не рисуется. Связи с
		серверами и прочим оборудованием здесь не перечисляются — их место в
		карточках коммутаторов
	</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($map->outside as $link) { ?>
			<tr>
				<td><?= $this->render('/ports/item', ['model' => $link['port'], 'include_tech' => true, 'reverse' => true]) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $this->render('/ports/item', ['model' => $link['peer'], 'include_tech' => true, 'reverse' => true]) ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
