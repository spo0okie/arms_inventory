<?php
/**
 * Панель Zabbix: активные проблемы узла + ссылка на него в веб-интерфейсе.
 * Данные нормализованы в ZabbixProvider::fetchProblems().
 */

use yii\helpers\Html;

/* @var $notFound bool узел не найден в Zabbix */
/* @var $problems array[] см. ZabbixProvider::fetchProblems() */
/* @var $metrics array см. ZabbixProvider::fetchMetrics() (пустой = метрик нет) */
/* @var $state array|null см. ZabbixProvider::fetchHostState() (null = не получили) */
/* @var $hostid string|null */
/* @var $urls array ссылки на разделы Zabbix (L0), пусто если web не задан */
/* @var $model \app\models\base\ArmsModel */
/* @var $provider \app\components\integrations\providers\ZabbixProvider */

if ($notFound) {
	echo '<span class="text-secondary opacity-75">узел не найден в Zabbix</span>';
	return;
}

//классы бейджа важности Zabbix: 0-1 серый, 2 инфо, 3 предупр., 4-5 опасность
$severityClass = static function (int $severity): string {
	if ($severity >= 4) return 'bg-danger';
	if ($severity === 3) return 'bg-warning text-dark';
	if ($severity === 2) return 'bg-info text-dark';
	return 'bg-secondary';
};

$formatter = Yii::$app->formatter;

//цвет полосы загрузки: до 70% норма, до 90% предупреждение, дальше опасно
$loadClass = static function (float $percent): string {
	if ($percent >= 90) return 'bg-danger';
	if ($percent >= 70) return 'bg-warning';
	return 'bg-success';
};

//аптайм словами: до суток - часы и минуты, дальше дни и часы
$uptimeText = static function (int $seconds): string {
	$days = intdiv($seconds, 86400);
	$hours = intdiv($seconds % 86400, 3600);
	if ($days) return $days.' дн '.$hours.' ч '.intdiv($seconds % 3600, 60).' мин';
	return $hours.' ч '.intdiv($seconds % 3600, 60).' мин';
};

//проценты без хвостовых нулей: 37% / 37.4%
$percentText = static function (float $percent): string {
	return rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.').'%';
};

//строка «подпись + полоса загрузки»
$loadBar = static function (string $label, float $percent) use ($loadClass, $percentText) {
	$width = max(2, $percent); //иначе при 0% не видно самой полосы
	return '<div class="d-flex align-items-center mt-1">'
		.'<small class="text-secondary text-truncate pe-2" style="width:7em" title="'.Html::encode($label).'">'
			.Html::encode($label).'</small>'
		.'<div class="progress flex-grow-1" style="height:1rem;max-width:22rem">'
			.'<div class="progress-bar '.$loadClass($percent).'" role="progressbar" '
				.'style="width:'.$width.'%">'.$percentText($percent).'</div>'
		.'</div></div>';
};

//состояние узла: выключенный мониторинг важнее доступности - у него
//данные не собираются вовсе
$stateBadge = null;
if (!is_null($state)) {
	if (!$state['monitored']) {
		$stateBadge = ['bg-secondary', 'мониторинг выключен', 'узел отключён в Zabbix - данные не собираются'];
	} elseif ($state['availability'] === 'down') {
		$stateBadge = ['bg-danger', 'недоступен', 'Zabbix не получает ответ от узла'];
	} elseif ($state['availability'] === 'up') {
		$stateBadge = ['bg-success', 'доступен', 'узел отвечает на проверки Zabbix'];
	} else {
		$stateBadge = ['bg-secondary', 'доступность неизвестна', 'проверок доступности не было'];
	}
}

//ссылки в Zabbix - иконками, чтобы не занимать место
$links = [
	'dashboard' => ['fa-tachometer-alt', 'Дашборд узла'],
	'latest' => ['fa-list', 'Последние данные'],
	'problems' => ['fa-exclamation-triangle', 'Проблемы'],
];

?>
<div class="d-flex align-items-center mb-1">
	<span>
		<?php if ($stateBadge) { ?>
			<span class="badge <?= $stateBadge[0] ?>" title="<?= Html::encode($stateBadge[2]) ?>"><?= $stateBadge[1] ?></span>
		<?php } ?>
		<?php if (count($problems)) { ?>
			<span class="badge bg-danger"><?= count($problems) ?></span> активных проблем
		<?php } else { ?>
			<span class="badge bg-success">OK</span> проблем нет
		<?php } ?>
	</span>
	<?php if (!empty($urls)) { ?>
		<small class="ps-2 text-nowrap">
			<?php foreach ($links as $section => $link) {
				if (empty($urls[$section])) continue;
				echo Html::a('<i class="fas '.$link[0].'"></i>', $urls[$section], [
					'target' => '_blank', 'rel' => 'noopener',
					'class' => 'pe-1', 'title' => $link[1],
				]);
			} ?>
		</small>
	<?php } ?>
</div>
<?php
if (!empty($metrics)) {
	//свежесть: без неё непонятно, живые это данные или снятые с машины,
	//выключенной неделю назад
	$clock = $metrics['clock'] ?? null;
	if (!is_null($clock)) {
		$stale = (time() - $clock) > $provider->staleAfter();
		echo '<div><small class="'.($stale ? 'text-warning' : 'text-secondary').'"'
			.' title="'.Html::encode($formatter->asDatetime($clock, 'php:d.m.Y H:i:s')).'">'
			.($stale ? '<i class="fas fa-exclamation-triangle"></i> данные устарели: ' : 'данные: ')
			.Html::encode($formatter->asRelativeTime($clock))
			.'</small></div>';
	} elseif (!empty($metrics['candidates'])) {
		//item'ы есть, а значений нет - сбор данных прекращён
		echo '<div><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> '
			.'данные не поступали более суток</small></div>';
	}

	//метрики: показываем только те, что нашлись в шаблоне узла
	if (!is_null($metrics['uptime'] ?? null)) { ?>
		<div><small><span class="text-secondary">Аптайм:</span> <?= $uptimeText($metrics['uptime']) ?></small></div>
	<?php }
	if (!is_null($metrics['cpu'] ?? null)) echo $loadBar('CPU', $metrics['cpu']);
	if (!is_null($metrics['ram'] ?? null)) echo $loadBar('Память', $metrics['ram']);
	foreach ($metrics['disks'] ?? [] as $disk) echo $loadBar($disk['name'], $disk['used']);
	if (count($problems)) echo '<hr class="my-2">';
}
?>
<?php foreach ($problems as $problem) { ?>
	<div class="mt-1">
		<span class="badge <?= $severityClass($problem['severity']) ?>"><?= Html::encode($problem['severity_name']) ?></span>
		<?= Html::encode($problem['name']) ?>
		<?php if (!empty($problem['since'])) { ?>
			<small class="text-secondary">с <?= $formatter->asDatetime($problem['since'], 'php:d.m.Y H:i') ?></small>
		<?php } ?>
	</div>
<?php } ?>
