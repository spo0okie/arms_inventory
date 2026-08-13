<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\Comps;
use app\models\base\ArmsModel;
use app\models\Techs;
use Yii;

/**
 * Панель Zabbix в карточке ОС/оборудования (docs/dev/integrations.md):
 * базовые метрики узла (аптайм, загрузка CPU и памяти, заполнение дисков),
 * активные проблемы (сработавшие триггеры) и ссылка на узел в
 * веб-интерфейсе Zabbix.
 *
 * Привязка объекта к узлу Zabbix — через hostid в external_links
 * (`Zabbix.hostid`). Постоянную привязку ведёт скрипт синхронизации
 * arms.zabbix (двусторонний: при заведении узла в Zabbix пишет hostid
 * обратно в инвентаризацию). Если hostid ещё не записан, панель делает
 * разовый поиск узла по FQDN/имени только для отображения (в БД не
 * пишет — панели не мутируют данные).
 *
 * Транспорт — Zabbix JSON-RPC 7.x (POST api_jsonrpc.php, Bearer-токен),
 * несколько запросов на панель. Не HTTP-шаблон, поэтому отдельный класс.
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'zabbix' => [
 *         'class' => \app\components\integrations\providers\ZabbixProvider::class,
 *         'api' => 'https://zabbix.local/zabbix/api_jsonrpc.php',
 *         'token' => '<API-токен сервисного пользователя Zabbix>',
 *         'web' => 'https://zabbix.local/zabbix', //для ссылок на узел (L0)
 *         //'metrics' => false, //не показывать аптайм/CPU/память/диски
 *         //'staleAfter' => 600, //с какого возраста данных писать «устарели»
 *         //'cacheTtl' => 60,
 *     ],
 * ],
 * ```
 */
class ZabbixProvider extends IntegrationProvider
{
	const PANEL = 'problems';

	/** метка external_links, под которой хранится hostid (см. arms.zabbix) */
	const HOSTID_KEY = 'Zabbix.hostid';

	/** окно поиска последнего значения метрики в истории, сек */
	const HISTORY_WINDOW = 86400;

	/** максимум файловых систем в панели (по одному запросу истории на ФС) */
	const MAX_DISKS = 8;

	/**
	 * @var int|null время самого свежего значения метрик за текущий сбор
	 * (заполняет lastPoint(), обнуляет fetchMetrics())
	 */
	private ?int $freshest = null;

	/** имена важностей триггеров Zabbix (severity 0..5) */
	const SEVERITIES = [
		0 => 'не классифицировано',
		1 => 'информация',
		2 => 'предупреждение',
		3 => 'средняя',
		4 => 'высокая',
		5 => 'чрезвычайная',
	];

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Zabbix';
	}

	public function isConfigured(): bool
	{
		return !empty($this->config['api']) && !empty($this->config['token']);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		return $model instanceof Comps || $model instanceof Techs;
	}

	public function binding(ArmsModel $model): ?string
	{
		//явная привязка из external_links (ведёт синк arms.zabbix)
		$hostid = $model->getExternalItem([static::HOSTID_KEY]);
		return $hostid ? (string)$hostid : null;
	}

	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				'ttl' => $this->config['cacheTtl'] ?? 60,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$hostid = $this->resolveHostid($model);
		if (is_null($hostid)) {
			return $this->renderView('problems', [
				'notFound' => true, 'problems' => [], 'hostid' => null,
				'hostUrl' => null, 'model' => $model, 'provider' => $this,
			]);
		}

		//состояние узла - отдельным «мягким» блоком: не смогли получить,
		//значит не показываем признак, но проблемы и метрики остаются
		$state = null;
		try {
			$state = $this->fetchHostState($hostid);
		} catch (\Throwable $e) {
			Yii::warning("Zabbix state for host $hostid failed: ".$e->getMessage(), __METHOD__);
		}

		$problems = $this->fetchProblems($hostid);

		//метрики - дополнение к проблемам: их отсутствие (нестандартный
		//шаблон, нет прав на историю) не должно ронять панель целиком
		$metrics = [];
		if (($this->config['metrics'] ?? true) !== false) {
			try {
				$metrics = $this->fetchMetrics($hostid);
			} catch (\Throwable $e) {
				Yii::warning("Zabbix metrics for host $hostid failed: ".$e->getMessage(), __METHOD__);
			}
		}

		return $this->renderView('problems', [
			'notFound' => false,
			'problems' => $problems,
			'metrics' => $metrics,
			'state' => $state,
			'hostid' => $hostid,
			'urls' => $this->hostUrls($hostid),
			'model' => $model,
			'provider' => $this,
		]);
	}

	/**
	 * hostid узла: явная привязка либо разовый поиск по FQDN/имени
	 * (только для отображения, без записи в БД)
	 * @throws \Throwable при недоступности Zabbix (ловит ядро)
	 */
	protected function resolveHostid(ArmsModel $model): ?string
	{
		if ($hostid = $this->binding($model)) return $hostid;
		return $this->searchHostid($model);
	}

	/**
	 * Поиск hostid по техническим именам объекта (host = FQDN для ОС,
	 * имя/инв.номер для оборудования)
	 */
	protected function searchHostid(ArmsModel $model): ?string
	{
		$names = [];
		if ($model instanceof Comps) {
			if (!empty($model->fqdn)) $names[] = $model->fqdn;
			if (!empty($model->name)) $names[] = $model->name;
		} else {
			/** @var Techs $model */
			if (!empty($model->name)) $names[] = $model->name;
			if (!empty($model->num)) $names[] = $model->num;
		}
		if (!$names) return null;

		$hosts = $this->zabbixCall('host.get', [
			'output' => ['hostid'],
			'filter' => ['host' => array_values(array_unique($names))],
			'limit' => 1,
		]);
		return $hosts[0]['hostid'] ?? null;
	}

	/**
	 * Активные проблемы узла (сработавшие триггеры), отсортированы по
	 * важности (убыв.), внутри важности - свежие сверху. Нормализованы
	 * для рендера.
	 *
	 * Сортировка своя, а не API: problem.get принимает в sortfield только
	 * eventid (иначе «Invalid parameter "/sortfield/1"»).
	 *
	 * @return array[] [{name, severity (int), severity_name, since (unix ts)}]
	 */
	protected function fetchProblems(string $hostid): array
	{
		$raw = $this->zabbixCall('problem.get', [
			'output' => ['name', 'severity', 'clock'],
			'hostids' => [$hostid],
			'recent' => false, //только незакрытые
			'sortfield' => 'eventid',
			'sortorder' => 'DESC',
		]);

		$problems = [];
		foreach ($raw ?? [] as $event) {
			$severity = (int)($event['severity'] ?? 0);
			$problems[] = [
				'name' => (string)($event['name'] ?? ''),
				'severity' => $severity,
				'severity_name' => static::SEVERITIES[$severity] ?? (string)$severity,
				'since' => (int)($event['clock'] ?? 0),
			];
		}

		usort($problems, static function ($a, $b) {
			return [$b['severity'], $b['since']] <=> [$a['severity'], $a['since']];
		});
		return $problems;
	}

	/**
	 * Базовые метрики узла: аптайм, загрузка CPU и памяти, заполнение
	 * файловых систем.
	 *
	 * Item'ы ищутся по ключам, а не по имени шаблона: у штатных шаблонов
	 * «... by Zabbix agent» ключи для Windows и Linux совпадают
	 * (system.uptime, system.cpu.util, vm.memory.utilization,
	 * vfs.fs.size[...,pused]), поэтому ОС различать не нужно. Учтены и
	 * «обратные» варианты старых шаблонов (idle/pfree/pavailable) - они
	 * пересчитываются в загрузку.
	 *
	 * Последнее значение берётся history.get по каждому item отдельно
	 * (в history.get нет группировки по item, а общий limit «съел» бы
	 * частые item'ы); окно ограничено сутками - без него запрос по всей
	 * истории тяжёлый.
	 *
	 * @return array [
	 *   'uptime' => int|null секунд,
	 *   'cpu' => float|null %, 'ram' => float|null %,
	 *   'disks' => [['name'=>string, 'used'=>float %], ...],
	 *   'clock' => int|null время самого свежего значения (unix ts) -
	 *     по нему видно, живые данные или снятые давно,
	 *   'candidates' => int сколько подходящих item'ов у узла вообще
	 *     (нашлись, но без свежих значений = данные не поступают)
	 * ]; метрика null, если такого item нет или нет свежих данных
	 * @throws \RuntimeException при ошибке API (ловит renderPanel)
	 */
	protected function fetchMetrics(string $hostid): array
	{
		$this->freshest = null;

		$items = $this->zabbixCall('item.get', [
			'output' => ['itemid', 'key_', 'value_type'],
			'hostids' => [$hostid],
			'search' => ['key_' => [
				'system.uptime', 'cpu.util', 'vm.memory.utilization',
				'vm.memory.size[', 'vfs.fs.size[', 'vfs.fs.dependent.size[',
			]],
			'searchByAny' => true,
			'monitored' => true, //только включённые item'ы наблюдаемых узлов
		]) ?? [];

		$found = ['uptime' => null, 'cpu' => null, 'ram' => null];
		$disks = [];
		$memory = []; //байтовые item'ы памяти: total/available/used
		$candidates = 0;
		foreach ($items as $item) {
			$parsed = $this->classifyItem((string)$item['key_']);
			if (is_null($parsed)) continue;
			$item = array_merge($item, $parsed);
			$candidates++;

			if ($parsed['metric'] === 'disk') {
				$disks[$parsed['name']] = $item; //дубли ключей на одну ФС схлопываем
				continue;
			}
			if ($parsed['metric'] === 'ram-bytes') {
				$memory[$parsed['name']] = $item;
				continue;
			}
			//прямой item выигрывает у «обратного» (100 - idle/free)
			$current = $found[$parsed['metric']];
			if (is_null($current) || ($current['invert'] && !$parsed['invert'])) {
				$found[$parsed['metric']] = $item;
			}
		}

		ksort($disks);
		$disks = array_slice($disks, 0, static::MAX_DISKS, true);

		$metrics = ['uptime' => null, 'cpu' => null, 'ram' => null, 'disks' => [],
			'clock' => null, 'candidates' => $candidates];

		if ($found['uptime']) {
			$uptime = $this->lastValue($found['uptime']);
			$metrics['uptime'] = is_null($uptime) ? null : (int)$uptime;
		}
		foreach (['cpu', 'ram'] as $metric) {
			if (!$found[$metric]) continue;
			$metrics[$metric] = $this->percent($found[$metric]);
		}
		//готового процента памяти нет - считаем из байтов (старые шаблоны)
		if (is_null($metrics['ram'])) $metrics['ram'] = $this->memoryFromBytes($memory);
		foreach ($disks as $name => $item) {
			$used = $this->percent($item);
			if (is_null($used)) continue;
			$metrics['disks'][] = ['name' => $name, 'used' => $used];
		}

		$metrics['clock'] = $this->freshest;
		return $metrics;
	}

	/**
	 * После какого возраста последних данных считать их устаревшими, сек.
	 * По умолчанию 10 минут: штатные шаблоны снимают метрики раз в минуту.
	 */
	public function staleAfter(): int
	{
		return (int)($this->config['staleAfter'] ?? 600);
	}

	/**
	 * Загрузка памяти в процентах из байтовых item'ов: занято/всего либо
	 * 100 - свободно/всего. Нужно для шаблонов, где готового процента нет.
	 * @param array $memory ['total'=>item, 'used'=>item, 'available'=>item]
	 */
	protected function memoryFromBytes(array $memory): ?float
	{
		if (empty($memory['total'])) return null;
		if (empty($memory['used']) && empty($memory['available'])) return null;

		$total = $this->lastValue($memory['total']);
		if (!$total) return null; //ноль и null одинаково непригодны как делитель

		if (!empty($memory['used'])) {
			$used = $this->lastValue($memory['used']);
			if (is_null($used)) return null;
			return max(0, min(100, round($used / $total * 100, 1)));
		}
		$available = $this->lastValue($memory['available']);
		if (is_null($available)) return null;
		return max(0, min(100, round(100 - $available / $total * 100, 1)));
	}

	/**
	 * К какой метрике относится ключ item'а и не «обратный» ли он
	 * (простой/свободно вместо загрузки/занято).
	 * Публичный - используется консольной диагностикой `yii zabbix/items`
	 * (показывает, распознан ли ключ узла).
	 *
	 * @return array|null ['metric'=>'uptime|cpu|ram|ram-bytes|disk',
	 *   'invert'=>bool, 'name'=>string имя ФС для disk / режим
	 *   total|available|used для ram-bytes] либо null если ключ не наш
	 */
	public function classifyItem(string $key): ?array
	{
		//параметры ключа: vfs.fs.size[/,pused] -> ['/', 'pused'].
		//регистр гасим только у имени ключа и режима: имя ФС - данные
		//(в Windows это C:, а не c:)
		$params = [];
		$key = trim($key);
		if (preg_match('/^([^\[]+)\[(.*)]$/', $key, $matches)) {
			$params = array_map('trim', explode(',', $matches[2]));
			$key = $matches[1];
		}
		$key = mb_strtolower($key);
		$modes = array_map('mb_strtolower', $params);
		$param = static function (int $index) use ($params): string {
			return trim($params[$index] ?? '', '"');
		};
		$mode = static function (int $index) use ($modes): string {
			return trim($modes[$index] ?? '', '"');
		};

		if ($key === 'system.uptime') return ['metric' => 'uptime', 'invert' => false];

		if (strpos($key, 'cpu.util') !== false) {
			//system.cpu.util[,idle] - простой, остальное (или без параметров) - загрузка
			return ['metric' => 'cpu', 'invert' => in_array('idle', $modes, true)];
		}

		if ($key === 'vm.memory.utilization') return ['metric' => 'ram', 'invert' => false];
		if ($key === 'vm.memory.size') {
			if ($mode(0) === 'pused') return ['metric' => 'ram', 'invert' => false];
			if (in_array($mode(0), ['pavailable', 'pfree'], true)) return ['metric' => 'ram', 'invert' => true];
			//байтовые режимы: процент считается парой с total (в старых
			//шаблонах готового процента памяти может не быть вовсе)
			$bytes = ['' => 'total', 'total' => 'total', 'available' => 'available',
				'free' => 'available', 'used' => 'used'];
			if (isset($bytes[$mode(0)]))
				return ['metric' => 'ram-bytes', 'invert' => false, 'name' => $bytes[$mode(0)]];
			return null;
		}

		if ($key === 'vfs.fs.size' || $key === 'vfs.fs.dependent.size') {
			$name = $param(0);
			if ($name === '') return null;
			if ($mode(1) === 'pused') return ['metric' => 'disk', 'invert' => false, 'name' => $name];
			if ($mode(1) === 'pfree') return ['metric' => 'disk', 'invert' => true, 'name' => $name];
		}

		return null;
	}

	/**
	 * Последнее значение item'а в процентах с учётом «обратности»
	 * @param array $item ['itemid','value_type','invert']
	 */
	protected function percent(array $item): ?float
	{
		$value = $this->lastValue($item);
		if (is_null($value)) return null;
		$value = $item['invert'] ? 100 - $value : $value;
		return max(0, min(100, round($value, 1)));
	}

	/**
	 * Последнее значение item'а из истории (null - нет данных за сутки).
	 * Публичный - используется консольной диагностикой `yii zabbix/items`.
	 * @param array $item ['itemid','value_type']
	 */
	public function lastValue(array $item): ?float
	{
		$point = $this->lastPoint($item);
		return is_null($point) ? null : $point['value'];
	}

	/**
	 * Последний замер item'а: значение и время снятия. Время нужно, чтобы
	 * отличить живые данные от снятых с давно выключенной машины, поэтому
	 * попутно запоминается самое свежее время за весь сбор метрик
	 * ({@see fetchMetrics()} обнуляет его перед сбором).
	 *
	 * @param array $item ['itemid','value_type']
	 * @return array|null ['value'=>float, 'clock'=>int unix ts]
	 */
	protected function lastPoint(array $item): ?array
	{
		$history = $this->zabbixCall('history.get', [
			'output' => ['value', 'clock'],
			'itemids' => [$item['itemid']],
			'history' => (int)$item['value_type'], //тип истории обязан совпадать с типом item'а
			'time_from' => time() - static::HISTORY_WINDOW,
			'sortfield' => 'clock',
			'sortorder' => 'DESC',
			'limit' => 1,
		]);
		if (!isset($history[0]['value'])) return null;

		$clock = (int)($history[0]['clock'] ?? 0);
		if ($clock > (int)$this->freshest) $this->freshest = $clock;
		return ['value' => (float)$history[0]['value'], 'clock' => $clock];
	}

	/**
	 * Состояние узла: включён ли мониторинг и доступен ли узел.
	 *
	 * Доступность в Zabbix 6+ живёт на интерфейсах (пассивные проверки),
	 * а для активного агента - в active_available узла (появился в 6.4,
	 * поэтому запрашивается «мягко»: на старых версиях API ответит
	 * ошибкой на неизвестное поле output, и мы повторяем запрос без него).
	 *
	 * Явная недоступность важнее доступности: пассивные проверки помечают
	 * интерфейс как 2 только после реальных отказов, а 0 значит «проверок
	 * не было» - тогда честнее сказать «неизвестно», чем «доступен».
	 *
	 * @return array|null ['monitored'=>bool, 'availability'=>'up|down|unknown',
	 *   'name'=>string] либо null если узел не найден
	 */
	protected function fetchHostState(string $hostid): ?array
	{
		$params = [
			'output' => ['hostid', 'host', 'name', 'status', 'active_available'],
			'hostids' => [$hostid],
			'selectInterfaces' => ['available'],
		];
		try {
			$hosts = $this->zabbixCall('host.get', $params);
		} catch (\RuntimeException $e) {
			$params['output'] = ['hostid', 'host', 'name', 'status'];
			$hosts = $this->zabbixCall('host.get', $params);
		}
		if (empty($hosts[0])) return null;
		$host = $hosts[0];

		$channels = [];
		foreach ($host['interfaces'] ?? [] as $interface) $channels[] = (int)($interface['available'] ?? 0);
		if (isset($host['active_available'])) $channels[] = (int)$host['active_available'];

		if (in_array(2, $channels, true)) $availability = 'down';
		elseif (in_array(1, $channels, true)) $availability = 'up';
		else $availability = 'unknown';

		return [
			'monitored' => (int)($host['status'] ?? 0) === 0,
			'availability' => $availability,
			'name' => (string)($host['name'] ?? $host['host'] ?? ''),
		];
	}

	/**
	 * Ссылки на узел в веб-интерфейсе Zabbix (L0): дашборд, последние
	 * данные, проблемы. Пустой массив, если web не задан.
	 * @return array [dashboard=>url, latest=>url, problems=>url]
	 */
	protected function hostUrls(string $hostid): array
	{
		$web = $this->config['web'] ?? null;
		if (!$web) return [];
		$web = rtrim($web, '/');
		//фильтры разделов ждут массив узлов: hostids[]=<id>
		$hostids = 'hostids%5B%5D='.urlencode($hostid);
		return [
			'dashboard' => $web.'/zabbix.php?action=host.dashboard.view&hostid='.urlencode($hostid),
			'latest' => $web.'/zabbix.php?action=latest.view&filter_set=1&'.$hostids,
			'problems' => $web.'/zabbix.php?action=problem.view&filter_set=1&'.$hostids,
		];
	}

	/**
	 * Произвольный вызов API - для консольной диагностики
	 * (`yii zabbix/*`, {@see \app\console\commands\ZabbixController}).
	 * В вебе не используется: панель ходит в API только из renderPanel().
	 * @return array|null result из ответа JSON-RPC
	 * @throws \RuntimeException при ошибке транспорта/API
	 */
	public function api(string $method, array $params): ?array
	{
		return $this->zabbixCall($method, $params);
	}

	/**
	 * Запрос к Zabbix JSON-RPC. Вынесен отдельным методом: тесты
	 * подменяют его, не трогая сеть.
	 * @return array|null result из ответа JSON-RPC
	 * @throws \RuntimeException при ошибке транспорта/API
	 */
	protected function zabbixCall(string $method, array $params): ?array
	{
		$request = json_encode([
			'jsonrpc' => '2.0',
			'method' => $method,
			'params' => $params,
			'id' => 1,
		], JSON_UNESCAPED_UNICODE);

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/json\r\n"
					."Authorization: Bearer ".$this->config['token']."\r\n",
				'content' => $request,
				'timeout' => $this->timeout(),
				'ignore_errors' => true,
			],
			'ssl' => [
				'verify_peer' => $this->config['verifySsl'] ?? false,
				'verify_peer_name' => $this->config['verifySsl'] ?? false,
			],
		]);

		$response = @file_get_contents($this->config['api'], false, $context);
		if ($response === false) throw new \RuntimeException('Zabbix API недоступен');

		$data = json_decode($response, true);
		if (!is_array($data)) throw new \RuntimeException('Некорректный ответ Zabbix API');
		if (isset($data['error'])) {
			throw new \RuntimeException('Zabbix API: '.($data['error']['data'] ?? $data['error']['message'] ?? 'ошибка'));
		}
		return $data['result'] ?? [];
	}
}
