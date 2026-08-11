<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\Comps;
use app\models\base\ArmsModel;
use app\models\Techs;

/**
 * Панель Zabbix в карточке ОС/оборудования (docs/dev/integrations.md):
 * активные проблемы (сработавшие триггеры) узла и ссылка на него в
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

		$problems = $this->fetchProblems($hostid);
		return $this->renderView('problems', [
			'notFound' => false,
			'problems' => $problems,
			'hostid' => $hostid,
			'hostUrl' => $this->hostUrl($hostid),
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
	 * важности (убыв.). Нормализованы для рендера.
	 * @return array[] [{name, severity (int), severity_name, since (unix ts)}]
	 */
	protected function fetchProblems(string $hostid): array
	{
		$raw = $this->zabbixCall('problem.get', [
			'output' => ['name', 'severity', 'clock'],
			'hostids' => [$hostid],
			'recent' => false, //только незакрытые
			'sortfield' => ['severity', 'eventid'],
			'sortorder' => ['DESC', 'DESC'],
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
		return $problems;
	}

	/** URL узла в веб-интерфейсе Zabbix (L0), null если web не задан */
	protected function hostUrl(string $hostid): ?string
	{
		$web = $this->config['web'] ?? null;
		if (!$web) return null;
		return rtrim($web, '/').'/zabbix.php?action=host.dashboard.view&hostid='.urlencode($hostid);
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
