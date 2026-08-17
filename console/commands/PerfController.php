<?php

namespace app\console\commands;

use app\helpers\AccessLogAnalyzer;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\helpers\Html;

/**
 * Отчёт о производительности по access-логу Apache
 * (механизм: docs/dev/perf-monitoring.md, применение: docs/help/admin/monitoring.md).
 *
 * Отвечает на вопросы "какие маршруты самые тяжелые", "что падает по таймауту",
 * "сколько 5xx" — по времени обработки %D, которое Apache пишет в конец строки
 * access-лога (формат combined_time, docker/apache/site.conf).
 *
 * Cron (ежедневный отчёт за вчера на adminEmail):
 *   yii perf/report --email=1   — раз в сутки, например в 0:10
 */
class PerfController extends Controller
{
	/** @var string файл(ы) access-лога: список через запятую, каждый элемент может
	 * быть glob-маской (ротированные куски и .gz читаются прозрачно, лишние дни
	 * отсекает фильтр по дате). Пусто = params['perf.access_log'] */
	public $file = '';

	/** @var int сколько маршрутов показывать в топах */
	public $top = 20;

	/** @var float порог "подозрение на таймаут", сек */
	public $slow = 29;

	/** @var string адрес для отправки отчёта; '1' = adminEmail; пусто = не отправлять */
	public $email = '';

	/** {@inheritdoc} */
	public function options($actionID)
	{
		return array_merge(parent::options($actionID), ['file', 'top', 'slow', 'email']);
	}

	/**
	 * Строит отчёт по маршрутам за сутки: топ по суммарному времени (создатели
	 * нагрузки), топ по p95 (медленные страницы), кандидаты в таймауты, 5xx.
	 *
	 * @param string|null $date день отчёта (Y-m-d); по умолчанию вчера
	 */
	public function actionReport($date = null)
	{
		$date = $date ?: date('Y-m-d', strtotime('yesterday'));
		$from = strtotime($date . ' 00:00:00');
		if ($from === false) {
			$this->stderr("не удалось разобрать дату '$date' (ожидается Y-m-d)\n");
			return ExitCode::DATAERR;
		}
		$to = $from + 86400;

		$analyzer = new AccessLogAnalyzer();
		$analyzer->slowSeconds = (float)$this->slow;
		$files = $this->resolveFiles();
		if (!count($files)) {
			$this->stderr("не найден ни один файл лога (--file или params['perf.access_log'])\n");
			return ExitCode::DATAERR;
		}
		foreach ($files as $file)
			if (!$analyzer->consumeFile($file, $from, $to))
				$this->stderr("не удалось прочитать $file\n");

		$totals = $analyzer->getTotals();
		if (!$totals['parsed']) {
			$this->stderr("за $date не разобрано ни одной строки"
				. " (прочитано {$totals['lines']}, вне периода {$totals['filtered']}, не разобрано {$totals['skipped']});"
				. " проверьте формат лога (combined_time) и дату\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}

		$report = $this->renderReport($date, $files, $analyzer);
		$this->stdout($report);

		if ($this->email !== '') {
			$address = $this->email === '1' ? Yii::$app->params['adminEmail'] : $this->email;
			$ok = Yii::$app->mailer->compose()
				->setTo($address)
				->setSubject(Yii::$app->name . ": отчёт о производительности за $date")
				->setHtmlBody('<pre style="font-family:monospace">' . Html::encode($report) . '</pre>')
				->send();
			$this->stdout($ok ? "отчёт отправлен: $address\n" : "не удалось отправить отчёт: $address\n");
			if (!$ok) return ExitCode::UNSPECIFIED_ERROR;
		}
		return ExitCode::OK;
	}

	/** разворачивает --file/params в список существующих файлов */
	protected function resolveFiles(): array
	{
		$masks = $this->file !== '' ? $this->file : (Yii::$app->params['perf.access_log'] ?? '');
		$files = [];
		foreach (array_filter(array_map('trim', explode(',', $masks))) as $mask)
			foreach (glob($mask) ?: [] as $file)
				if (is_file($file)) $files[] = $file;
		return array_unique($files);
	}

	/** собирает текст отчёта (он же уходит в письмо) */
	protected function renderReport(string $date, array $files, AccessLogAnalyzer $analyzer): string
	{
		$totals = $analyzer->getTotals();
		$stats = $analyzer->routeStats();

		$out = "Отчёт о производительности за $date\n";
		$out .= 'Файлы: ' . implode(', ', $files) . "\n";
		$statuses = $totals['statuses'];
		ksort($statuses);
		$out .= "Запросов: {$totals['parsed']}"
			. ' (' . implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($statuses), $statuses)) . ')'
			. ($totals['noDuration'] ? ", без тайминга: {$totals['noDuration']}" : '')
			. ($totals['skipped'] ? ", не разобрано строк: {$totals['skipped']}" : '')
			. "\n";

		//разрез api/ui: чей вклад в нагрузку больше - людей или синхронизаций
		$split = ['api' => ['count' => 0, 'sumMs' => 0], 'ui' => ['count' => 0, 'sumMs' => 0]];
		foreach ($stats as $route => $stat) {
			$kind = preg_match('#^\S+ /api(/|$)#', $route) ? 'api' : 'ui';
			$split[$kind]['count'] += $stat['count'];
			$split[$kind]['sumMs'] += $stat['sumMs'];
		}
		$out .= sprintf("Разрез: ui %d запросов / %s; api %d запросов / %s\n\n",
			$split['ui']['count'], static::fmtMs($split['ui']['sumMs']),
			$split['api']['count'], static::fmtMs($split['api']['sumMs']));

		//топ по суммарному времени: кто реально создаёт нагрузку (count*avg)
		uasort($stats, fn($a, $b) => $b['sumMs'] <=> $a['sumMs']);
		$out .= "Топ-{$this->top} маршрутов по суммарному времени (создатели нагрузки):\n";
		$out .= $this->routesTable(array_slice($stats, 0, $this->top, true));

		//топ по p95: медленные для пользователя, даже если редкие
		$timed = array_filter($stats, fn($s) => $s['p95'] !== null);
		uasort($timed, fn($a, $b) => $b['p95'] <=> $a['p95']);
		$out .= "\nТоп-{$this->top} маршрутов по p95 (медленные страницы):\n";
		$out .= $this->routesTable(array_slice($timed, 0, $this->top, true));

		$out .= "\nПодозрения на таймаут (дольше {$analyzer->slowSeconds} сек): "
			. $analyzer->getSlowTotal() . "\n";
		$out .= $this->requestsTable($analyzer->getSlowRequests(), $analyzer->getSlowTotal());

		$out .= "\nЗапросы 5xx: " . $analyzer->getErrorsTotal() . "\n";
		$out .= $this->requestsTable($analyzer->getErrorRequests(), $analyzer->getErrorsTotal());

		return $out;
	}

	/** таблица агрегатов по маршрутам */
	protected function routesTable(array $stats): string
	{
		$rows = [];
		foreach ($stats as $route => $s)
			$rows[] = [
				$route, $s['count'],
				static::fmtMs($s['sumMs']),
				static::fmtMs($s['avgMs']), static::fmtMs($s['p50']),
				static::fmtMs($s['p95']), static::fmtMs($s['p99']), static::fmtMs($s['maxMs']),
				$s['count5xx'] ?: '',
			];
		return Table::widget([
			'headers' => ['маршрут', 'кол-во', 'сумма', 'ср.', 'p50', 'p95', 'p99', 'макс', '5xx'],
			'rows' => $rows,
		]);
	}

	/** таблица отдельных запросов (таймауты, 5xx) */
	protected function requestsTable(array $requests, int $total): string
	{
		if (!count($requests)) return "";
		$rows = [];
		foreach ($requests as $r)
			$rows[] = [
				$r['timeLocal'], //время "как в логе" - для сверки с grep по файлу
				static::fmtMs($r['durationMs']),
				$r['status'],
				$r['path'],
				$r['ip'],
			];
		$out = Table::widget([
			'headers' => ['время', 'длит.', 'код', 'url', 'ip'],
			'rows' => $rows,
		]);
		if ($total > count($requests))
			$out .= '...показаны первые ' . count($requests) . " из $total\n";
		return $out;
	}

	/** миллисекунды в человекочитаемое: 234ms / 12.3s */
	protected static function fmtMs($ms): string
	{
		if ($ms === null) return '-';
		if ($ms < 1000) return round($ms) . 'ms';
		return round($ms / 1000, 1) . 's';
	}
}
