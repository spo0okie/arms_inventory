<?php

namespace app\helpers;

/**
 * Разбор и агрегация access-лога Apache для отчёта о производительности
 * (команда `yii perf/report`, механизм — docs/dev/perf-monitoring.md).
 *
 * Понимает формат `combined_time` — стандартный combined, дополненный в конце
 * строки временем обработки запроса "%Dus" в микросекундах
 * (см. docker/apache/site.conf). Строки старого формата combined (без времени)
 * учитываются в счётчиках запросов/статусов, но не участвуют в таймингах.
 *
 * Использование:
 * ```php
 * $analyzer = new AccessLogAnalyzer();
 * $analyzer->consumeFile('/var/log/apache2/access.log', $from, $to);
 * $stats = $analyzer->routeStats();     //агрегаты по маршрутам
 * $slow  = $analyzer->getSlowRequests();//кандидаты в таймауты
 * ```
 *
 * Класс не зависит от Yii (чистый PHP) — покрыт юнит-тестами
 * tests/unit/helpers/AccessLogAnalyzerTest.php.
 */
class AccessLogAnalyzer
{
	/** @var float порог "подозрение на таймаут", сек. Запросы дольше попадают в список slow
	 * (29 по умолчанию: чуть меньше типовых 30 сек лимитов php/proxy, чтобы поймать упершихся в лимит) */
	public $slowSeconds = 29.0;

	/** @var int предел длины списков slow/5xx (защита памяти на аномальных логах);
	 * сверх предела запросы только считаются */
	public $listLimit = 200;

	/** @var array маршрут => ['count','sumMs','durations'=>[],'statuses'=>['2xx'=>N,...]] */
	protected $routes = [];

	/** @var array запросы дольше slowSeconds: ['time','durationMs','status','route','ip','user'] */
	protected $slow = [];
	/** @var int всего slow-запросов (включая не попавшие в список из-за listLimit) */
	protected $slowTotal = 0;

	/** @var array запросы с кодом 5xx (та же структура, что slow) */
	protected $errors = [];
	/** @var int всего 5xx */
	protected $errorsTotal = 0;

	/** @var array сквозные счётчики */
	protected $totals = [
		'lines' => 0,		//строк прочитано
		'parsed' => 0,		//строк разобрано (после фильтра по времени)
		'skipped' => 0,		//строк не разобрано (чужой формат, битые)
		'filtered' => 0,	//строк вне запрошенного периода
		'noDuration' => 0,	//разобрано, но без времени обработки (старый формат)
		'statuses' => [],	//класс статуса ('2xx'...) => счётчик
	];

	/** разбор одной строки combined/combined_time
	 * @return array|null null = строка не разобрана;
	 *   ключи: ip, user, timestamp, method, path, status, durationMs (null у строк без %D)
	 */
	public static function parseLine(string $line): ?array
	{
		//combined: %h %l %u %t "%r" %>s %b ["referer" "user-agent"] [%Dus]
		if (!preg_match(
			'/^(?<ip>\S+) \S+ (?<user>\S+) \[(?<time>[^\]]+)\] "(?<request>[^"]*)" (?<status>\d{3}) \S+'
			. '( "[^"]*" "[^"]*")?( (?<duration>\d+)us)?\s*$/',
			$line, $m
		)) return null;

		$time = \DateTime::createFromFormat('d/M/Y:H:i:s O', $m['time']);
		if ($time === false) return null;

		//%r = "GET /path?query HTTP/1.1"; у оборванных запросов бывает "-" или мусор
		$method = '-';
		$path = $m['request'];
		if (preg_match('/^(\S+) (\S+)/', $m['request'], $r)) {
			$method = $r[1];
			$path = $r[2];
		}

		return [
			'ip' => $m['ip'],
			'user' => $m['user'],
			'timestamp' => $time->getTimestamp(),
			//время "как в логе" (без пересчёта в TZ процесса) - для сверки с grep по файлу
			'timeLocal' => $time->format('H:i:s'),
			'method' => $method,
			'path' => $path,
			'status' => (int)$m['status'],
			'durationMs' => isset($m['duration']) && $m['duration'] !== ''
				? (int)$m['duration'] / 1000
				: null,
		];
	}

	/**
	 * Нормализует запрос до "маршрута": отрезает query string, числовые сегменты
	 * пути заменяет на {id} — иначе каждая карточка (/services/view?id=5,
	 * /api/techs/123) была бы отдельной строкой агрегата.
	 */
	public static function normalizeRoute(string $method, string $path): string
	{
		$path = explode('?', $path, 2)[0];
		$segments = explode('/', $path);
		foreach ($segments as &$segment)
			if ($segment !== '' && ctype_digit($segment)) $segment = '{id}';
		unset($segment);
		return $method . ' ' . implode('/', $segments);
	}

	/**
	 * Учитывает одну строку лога.
	 * @param int|null $from учитывать только запросы с timestamp >= $from
	 * @param int|null $to и < $to (ротированные файлы содержат чужие дни — фильтр решает)
	 */
	public function consumeLine(string $line, ?int $from = null, ?int $to = null): void
	{
		$this->totals['lines']++;
		$entry = static::parseLine($line);
		if ($entry === null) {
			$this->totals['skipped']++;
			return;
		}
		if (($from !== null && $entry['timestamp'] < $from) || ($to !== null && $entry['timestamp'] >= $to)) {
			$this->totals['filtered']++;
			return;
		}
		$this->totals['parsed']++;

		$statusClass = intdiv($entry['status'], 100) . 'xx';
		$this->totals['statuses'][$statusClass] = ($this->totals['statuses'][$statusClass] ?? 0) + 1;

		$route = static::normalizeRoute($entry['method'], $entry['path']);
		if (!isset($this->routes[$route]))
			$this->routes[$route] = ['count' => 0, 'sumMs' => 0.0, 'durations' => [], 'statuses' => []];
		$stat = &$this->routes[$route];
		$stat['count']++;
		$stat['statuses'][$statusClass] = ($stat['statuses'][$statusClass] ?? 0) + 1;

		if ($entry['durationMs'] === null) $this->totals['noDuration']++;
		else {
			$stat['sumMs'] += $entry['durationMs'];
			$stat['durations'][] = $entry['durationMs'];
		}
		unset($stat);

		$item = [
			'time' => $entry['timestamp'],
			'timeLocal' => $entry['timeLocal'],
			'durationMs' => $entry['durationMs'],
			'status' => $entry['status'],
			'route' => $route,
			'path' => $entry['path'],
			'ip' => $entry['ip'],
			'user' => $entry['user'],
		];
		if ($entry['durationMs'] !== null && $entry['durationMs'] >= $this->slowSeconds * 1000) {
			$this->slowTotal++;
			if (count($this->slow) < $this->listLimit) $this->slow[] = $item;
		}
		if ($entry['status'] >= 500) {
			$this->errorsTotal++;
			if (count($this->errors) < $this->listLimit) $this->errors[] = $item;
		}
	}

	/**
	 * Скармливает файл лога построчно. Файлы .gz читаются прозрачно
	 * (gzopen на несжатом файле читает его как есть) — ротированные логи
	 * не требуют распаковки.
	 * @return bool false = файл не открылся
	 */
	public function consumeFile(string $file, ?int $from = null, ?int $to = null): bool
	{
		$handle = @gzopen($file, 'r');
		if ($handle === false) return false;
		while (($line = gzgets($handle, 65536)) !== false)
			$this->consumeLine($line, $from, $to);
		gzclose($handle);
		return true;
	}

	/**
	 * Агрегаты по маршрутам.
	 * @return array маршрут => [count, sumMs, avgMs, p50, p95, p99, maxMs (мс),
	 *   statuses (класс=>счётчик), count5xx]; тайминги null, если по маршруту
	 *   не было строк со временем
	 */
	public function routeStats(): array
	{
		$result = [];
		foreach ($this->routes as $route => $stat) {
			$durations = $stat['durations'];
			sort($durations);
			$n = count($durations);
			$result[$route] = [
				'count' => $stat['count'],
				'sumMs' => $stat['sumMs'],
				'avgMs' => $n ? $stat['sumMs'] / $n : null,
				'p50' => $n ? static::percentile($durations, 50) : null,
				'p95' => $n ? static::percentile($durations, 95) : null,
				'p99' => $n ? static::percentile($durations, 99) : null,
				'maxMs' => $n ? end($durations) : null,
				'statuses' => $stat['statuses'],
				'count5xx' => $stat['statuses']['5xx'] ?? 0,
			];
		}
		return $result;
	}

	/** перцентиль по отсортированному по возрастанию массиву (метод ближайшего ранга) */
	public static function percentile(array $sorted, float $p): float
	{
		$n = count($sorted);
		if (!$n) throw new \InvalidArgumentException('пустой массив');
		$index = (int)ceil($p / 100 * $n) - 1;
		return $sorted[max(0, min($index, $n - 1))];
	}

	/** @return array запросы дольше slowSeconds (не больше listLimit записей) */
	public function getSlowRequests(): array	{ return $this->slow; }

	/** @return int всего запросов дольше slowSeconds */
	public function getSlowTotal(): int			{ return $this->slowTotal; }

	/** @return array запросы 5xx (не больше listLimit записей) */
	public function getErrorRequests(): array	{ return $this->errors; }

	/** @return int всего запросов 5xx */
	public function getErrorsTotal(): int		{ return $this->errorsTotal; }

	/** @return array сквозные счётчики: lines, parsed, skipped, filtered, noDuration, statuses */
	public function getTotals(): array			{ return $this->totals; }
}
