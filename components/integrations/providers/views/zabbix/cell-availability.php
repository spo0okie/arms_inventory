<?php
/**
 * Ячейка грида «Zabbix»: доступность узла + аптайм (списочный режим,
 * docs/dev/integrations.md §5 «Колонки в списках»).
 * Данные — ZabbixProvider::renderCells(): состояние из fetchHostStates()
 * (семантика бейджа — та же, что в панели problems.php), аптайм из
 * fetchUptimes(). Кэш общий на инстанс: HTML одинаков для всех зрителей,
 * L0-ссылка на дашборд допустима.
 */

use yii\helpers\Html;

/* @var $state array|null ['monitored','availability','name'] либо null - узла нет в Zabbix */
/* @var $uptime array|null ['uptime'=>сек, 'clock'=>unix ts] либо null - данных нет */
/* @var $staleAfter int возраст данных, после которого аптайм не показываем, сек */
/* @var $url string|null ссылка на дашборд узла (L0), null если web не задан */
/* @var $compact bool режим вложенного списка (для ячейки не отличается) */

if (is_null($state)) {
	echo '<span class="text-secondary opacity-75">нет в Zabbix</span>';
	return;
}

//бейдж состояния: выключенный мониторинг важнее доступности (данные не
//собираются вовсе) - приоритеты те же, что в панели problems.php
if (!$state['monitored']) {
	$badge = ['bg-secondary', 'не мониторится', 'узел отключён в Zabbix - данные не собираются'];
} elseif ($state['availability'] === 'down') {
	$badge = ['bg-danger', 'недоступен', 'Zabbix не получает ответ от узла'];
} elseif ($state['availability'] === 'up') {
	$badge = ['bg-success', 'доступен', 'узел отвечает на проверки Zabbix'];
} else {
	$badge = ['bg-secondary', 'неизвестно', 'проверок доступности не было'];
}

//аптайм коротко (14д 3ч / 5ч 12м); устаревший замер не показываем -
//это данные с выключенной машины, бейдж уже сказал главное
$uptimeText = null;
if (!is_null($uptime) && (time() - $uptime['clock']) <= $staleAfter) {
	$seconds = $uptime['uptime'];
	$days = intdiv($seconds, 86400);
	$hours = intdiv($seconds % 86400, 3600);
	$uptimeText = $days ? $days.'д '.$hours.'ч' : $hours.'ч '.intdiv($seconds % 3600, 60).'м';
}

?>
<span class="text-nowrap">
	<span class="badge <?= $badge[0] ?>" title="<?= Html::encode($badge[2]) ?>"><?= $badge[1] ?></span>
	<?php if (!is_null($uptimeText)) { ?>
		<small title="Аптайм узла по данным Zabbix"><?= Html::encode($uptimeText) ?></small>
	<?php } ?>
	<?php if (!empty($url)) {
		echo Html::a('<i class="fas fa-tachometer-alt"></i>', $url, [
			'target' => '_blank', 'rel' => 'noopener',
			'class' => 'ps-1', 'title' => 'Дашборд узла в Zabbix',
		]);
	} ?>
</span>
