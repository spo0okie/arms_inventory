<?php
/**
 * Всё, что коммутатор рассказал о себе, — по портам, без выводов.
 *
 * Основная таблица показывает ВЫВОДЫ (вердикт, найденное оборудование), и
 * когда вывод непонятен («почему транзит?»), человеку нужны исходные данные
 * ровно в том виде, в каком их отдал коммутатор: все адреса порта с VLAN, все
 * настроенные VLAN, состояние, скорость, подпись, группа, соседи. Тут нет
 * сопоставления с инвентаризацией и нет фильтра «физический/нет»: Po1 и
 * Vlan120 — тоже строки, потому что коммутатор их вернул.
 */

use app\components\integrations\providers\MacSearchProvider;
use app\models\Techs;
use yii\helpers\Html;

/* @var $data array ответ сервиса: rows / ports / neighbors */

//собираем по имени порта всё из трёх источников; порядок - паспорта (он
//аппаратный, по ifIndex), потом остальные по имени
$ports = [];
foreach ($data['ports'] ?? [] as $item) {
	$name = (string)($item['name'] ?? '');
	if ($name === '') continue;
	$ports[$name] = ['passport' => $item, 'macs' => [], 'neighbors' => []];
}
foreach ($data['rows'] ?? [] as $row) {
	$name = (string)($row['port'] ?? '');
	if ($name === '') continue;
	if (!isset($ports[$name])) $ports[$name] = ['passport' => null, 'macs' => [], 'neighbors' => []];
	$ports[$name]['macs'][] = $row;
}
foreach ($data['neighbors'] ?? [] as $neighbor) {
	$name = (string)($neighbor['port'] ?? '');
	if ($name === '') continue;
	if (!isset($ports[$name])) $ports[$name] = ['passport' => null, 'macs' => [], 'neighbors' => []];
	$ports[$name]['neighbors'][] = $neighbor;
}

$hasPassport = count($data['ports'] ?? []) > 0;
$hasNeighbors = count($data['neighbors'] ?? []) > 0;

//тип интерфейса словами: цифра из IF-MIB человеку ничего не говорит
$types = [6 => 'ethernet', 161 => 'агрегат', 53 => 'виртуальный', 24 => 'loopback',
	131 => 'туннель', 117 => 'gigabitEthernet', 1 => 'другой'];
?>
<table class="table table-sm small mt-1">
	<thead><tr>
		<th>Порт</th>
		<?php if ($hasPassport) { ?>
			<th title="Тип интерфейса (ifType): розетка на корпусе только у ethernet">Тип</th>
			<th title="admin / oper: включён ли администратором / есть ли линк">Состояние</th>
			<th>Скорость</th>
			<th title="Описание порта на самом коммутаторе (ifAlias)">Подпись</th>
			<th title="Агрегированный канал, в который собран порт">Группа</th>
			<th title="Настроенные VLAN; нетегированный жирным">VLAN</th>
		<?php } ?>
		<th title="Адреса из таблицы MAC и VLAN, в котором каждый замечен">Адреса</th>
		<?php if ($hasNeighbors) { ?><th>Соседи (LLDP/CDP)</th><?php } ?>
	</tr></thead>
	<tbody>
	<?php foreach ($ports as $name => $port) { $passport = $port['passport']; ?>
		<tr>
			<td class="text-nowrap"><?= Html::encode($name) ?></td>
			<?php if ($hasPassport) { ?>
				<td class="text-nowrap"><?php
					$type = (int)($passport['type'] ?? 0);
					echo $passport ? Html::encode($types[$type] ?? ($type ?: '')) : '';
				?></td>
				<td class="text-nowrap"><?php if ($passport) {
					echo Html::encode(($passport['admin'] ?? '').' / '.($passport['oper'] ?? ''));
				} ?></td>
				<td class="text-nowrap"><?= $passport && !empty($passport['speed'])
					? Html::encode($passport['speed']) : '' ?></td>
				<td><?= $passport ? Html::encode((string)($passport['description'] ?? '')) : '' ?></td>
				<td class="text-nowrap"><?= $passport ? Html::encode((string)($passport['aggregate'] ?? '')) : '' ?></td>
				<td><?php
					$vlans = [];
					foreach ($passport['vlans'] ?? [] as $vlan) {
						$vlans[] = !empty($vlan['untagged'])
							? '<b>'.Html::encode($vlan['vlan']).'</b>' : Html::encode($vlan['vlan']);
					}
					echo implode(', ', $vlans);
				?></td>
			<?php } ?>
			<td><?php
				//каждый адрес отдельной строкой со своим VLAN: именно это и
				//нужно, когда разбираешься, почему порт «транзит»
				$macs = [];
				foreach ($port['macs'] as $row) {
					$macs[] = '<span class="mac_address">'.Html::encode(
						Techs::formatMacs((string)($row['mac'] ?? ''))).'</span>'
						.(strlen((string)($row['vlan'] ?? '')) ? ' <span class="text-secondary">vlan '
							.Html::encode($row['vlan']).'</span>' : '');
				}
				echo implode('<br>', $macs);
			?></td>
			<?php if ($hasNeighbors) { ?>
				<td><?php
					$neighbors = [];
					foreach ($port['neighbors'] as $neighbor) {
						$neighbors[] = Html::encode(trim(($neighbor['remote_name'] ?? '').' '
							.($neighbor['remote_port'] ?? '')))
							.(!empty($neighbor['remote_mac']) ? ' <span class="mac_address">'
								.Html::encode(Techs::formatMacs($neighbor['remote_mac'])).'</span>' : '')
							.' <span class="text-secondary">'.Html::encode($neighbor['protocol'] ?? '').'</span>';
					}
					echo implode('<br>', $neighbors);
				?></td>
			<?php } ?>
		</tr>
	<?php } ?>
	</tbody>
</table>
<div>
	портов: <?= count($ports) ?>, адресов: <?= count($data['rows'] ?? []) ?><?php
	if ($hasPassport) { ?>, паспорт: <?= count($data['ports']) ?> интерфейсов<?php }
	if ($hasNeighbors) { ?>, соседей: <?= count($data['neighbors']) ?><?php }
	if (!empty($data['duration'])) { ?>, опрос занял <?= (float)$data['duration'] ?> c<?php }
	if (!empty($data['cached'])) { ?>, ответ из кэша сервиса<?php } ?>
</div>
