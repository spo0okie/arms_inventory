<?php
/**
 * Диагностика опроса: что каждая цель отдаёт по CLI и по SNMP и почему нет.
 *
 * «Опрошено 8/9» — поверхностно: не видно, ПОЧЕМУ карта неполная. Здесь по
 * каждому коммутатору — доступность обоих транспортов (с причиной: модель не
 * умеет / сеть / учётные данные) и по каждой возможности (таблица MAC, соседи
 * с признаком «LLDP включён», ARP, агрегация, порты) — какой командой или
 * веткой MIB спрашивали и чем кончилось. Отказ по правам («команда
 * отвергнута») показывается явно, а не как безликое «данных нет».
 */

use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/* @var $capabilities array отчёты сервиса по целям (ключ capabilities ответа) */
/* @var $switches \app\models\Techs[] опрошенные коммутаторы (id => модель) */
/* @var $compact bool */

if (!is_array($capabilities) || !count($capabilities)) return;

//подписи возможностей: слева то, что спрашивали, в скобках — чем
$cliLabels = [
	'fdb' => 'Таблица MAC (FDB)',
	'neighbors' => 'Соседи (LLDP/CDP)',
	'arp' => 'ARP-таблица',
	'lag' => 'Группы агрегации',
	'interfaces' => 'Список портов',
];
$snmpLabels = [
	'interfaces' => 'Список портов (IF-MIB)',
	'lag' => 'Группы агрегации (ifStackTable/LAG-MIB)',
	'vlan' => 'VLAN (Q-BRIDGE)',
	'identity' => 'Визитка (SYSTEM/ENTITY/BRIDGE)',
	'fdb' => 'Таблица MAC (BRIDGE-MIB)',
	'lldp' => 'Соседи (LLDP-MIB)',
	'arp' => 'ARP (ipNetToMedia)',
];

/** Статус возможности одним значком со словом; примечание/детали — тултипом */
$status = static function (array $entry): string {
	$note = trim((string)($entry['note'] ?? ''));
	$detail = trim((string)($entry['detail'] ?? ''));
	$ttip = trim($note.($detail !== '' && $detail !== $note ? "\n".$detail : ''));
	$badge = [
		'ok' => ['fas fa-check text-success', 'отдаёт'],
		'empty' => ['fas fa-circle-notch text-secondary opacity-50', 'данных нет'],
		'denied' => ['fas fa-ban text-danger', 'отказ (нет прав или команда не принята)'],
		'error' => ['fas fa-times text-danger', 'сбой'],
		'unsupported' => ['fas fa-minus text-secondary opacity-50', 'не умеет'],
		'skipped' => ['fas fa-ellipsis-h text-secondary opacity-50', 'не проверялось'],
	][$entry['status'] ?? 'skipped'] ?? ['fas fa-question text-secondary', (string)($entry['status'] ?? '?')];

	$html = '<i class="'.$badge[0].'"></i> '.Html::encode($badge[1]);
	if (isset($entry['rows']) && ($entry['status'] ?? '') === 'ok')
		$html .= ' <span class="text-secondary">('.(int)$entry['rows'].')</span>';
	return $ttip === '' ? $html
		: '<span qtip_ttip="'.Html::encode($ttip).'">'.$html.'</span>';
};

/** Строка доступности транспорта: да / нет с причиной / не применим */
$availability = static function (array $section): string {
	$note = trim((string)($section['note'] ?? ''));
	$detail = trim((string)($section['detail'] ?? ''));
	$available = $section['available'] ?? null;
	if ($available === true)
		return '<i class="fas fa-check text-success"></i> доступен';
	$text = $available === false
		? '<i class="fas fa-times text-danger"></i> недоступен: '.Html::encode($note ?: 'причина не указана')
		: '<i class="fas fa-minus text-secondary"></i> '.Html::encode($note ?: 'не применим');
	return $detail !== '' && $detail !== $note
		? '<span qtip_ttip="'.Html::encode($detail).'">'.$text.'</span>' : $text;
};

/** Строки возможностей одного транспорта */
$rows = static function (array $section, array $labels) use ($status): string {
	$html = '';
	foreach ($labels as $key => $label) {
		$entry = $section['capabilities'][$key] ?? null;
		if (!is_array($entry)) continue;
		//у соседей отдельная ценность - знать, включён ли LLDP вообще
		$extra = '';
		if (array_key_exists('lldp_enabled', $entry)) {
			$extra = $entry['lldp_enabled'] === true
				? ' <span class="text-success small">LLDP включён</span>'
				: ($entry['lldp_enabled'] === false
					? ' <span class="text-warning small">протокол обнаружения выключен</span>'
					: '');
		}
		$html .= '<tr>'
			.'<td class="ps-4">'.Html::encode($label).'</td>'
			.'<td class="text-nowrap">'.$status($entry).$extra.'</td>'
			.'<td class="text-secondary"><code class="small">'
				.Html::encode(implode('; ', $entry['commands'] ?? [])).'</code></td>'
			.'</tr>';
	}
	return $html;
};
?>
<table class="table table-sm w-auto small mb-2">
	<tbody>
	<?php foreach ($capabilities as $diag) {
		$tech = $switches[$diag['target'] ?? 0] ?? null;
		$title = is_object($tech)
			? ModelWidget::widget(['model' => $tech, 'options' => ['static_view' => true]])
			: Html::encode($diag['host'] ?? '');
		?>
		<tr class="table-secondary">
			<th colspan="3"><?= $title ?>
				<span class="text-secondary fw-normal"><?= Html::encode($diag['host'] ?? '')
					?> · драйвер <?= Html::encode($diag['driver'] ?? '') ?></span></th>
		</tr>
		<?php foreach ([['cli', 'CLI (ssh)', $cliLabels], ['snmp', 'SNMP', $snmpLabels]]
			as [$key, $name, $labels]) {
			$section = $diag[$key] ?? null;
			if (!is_array($section)) continue;
			?>
			<tr>
				<td class="ps-2 fw-bold"><?= Html::encode($name) ?></td>
				<td colspan="2"><?= $availability($section) ?></td>
			</tr>
			<?= $rows($section, $labels) ?>
		<?php } ?>
	<?php } ?>
	</tbody>
</table>
