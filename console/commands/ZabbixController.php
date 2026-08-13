<?php

namespace app\console\commands;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\ZabbixProvider;
use app\helpers\DocsHelper;
use app\models\base\ArmsModel;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Диагностика интеграции с Zabbix (провайдер 'zabbix' из
 * params-local.php, см. docs/dev/integrations.md). Для проверки настройки
 * инстанса против живого Zabbix - юнит-тесты в сеть не ходят.
 *
 * Использование:
 *   yii zabbix/ping                 доступность API и валидность токена
 *   yii zabbix/find <имя>           поиск узлов по имени (hostid для привязки)
 *   yii zabbix/host <hostid>        узел и его активные проблемы
 *   yii zabbix/items <hostid> [ф]   item'ы метрик: распознан ли ключ и
 *                                   есть ли свежее значение
 *   yii zabbix/object <класс> <id>  что покажет панель в карточке объекта
 *                                   ARMS (comps 42, techs 17)
 */
class ZabbixController extends Controller
{
	/**
	 * Провайдер из реестра (включён в params + isConfigured)
	 * @return ZabbixProvider|null
	 */
	protected function provider(): ?ZabbixProvider
	{
		$provider = IntegrationsRegistry::provider('zabbix');
		if (!$provider instanceof ZabbixProvider) {
			$this->stderr("Интеграция 'zabbix' не включена или не настроена"
				." (params-local.php: integrations.zabbix, нужны api и token)\n");
			return null;
		}
		return $provider;
	}

	/**
	 * Доступность API и валидность токена: запрашиваем количество видимых
	 * токену узлов - проверяет разом URL, TLS, токен и его права.
	 * @return int
	 */
	public function actionPing()
	{
		if (!$provider = $this->provider()) return ExitCode::CONFIG;
		$this->stdout('API: '.$provider->config['api']."\n");
		try {
			$count = $provider->api('host.get', ['countOutput' => true]);
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		//countOutput возвращает число строкой, а не массив
		$this->stdout('OK: API отвечает, токен принят; узлов видно: '
			.(is_array($count) ? count($count) : (string)$count)."\n");
		return ExitCode::OK;
	}

	/**
	 * Поиск узлов по имени - чтобы узнать hostid для привязки
	 * (external_links: {"Zabbix.hostid":"..."}).
	 * @param string $name часть имени узла (host или visible name)
	 * @return int
	 */
	public function actionFind($name)
	{
		if (!$provider = $this->provider()) return ExitCode::CONFIG;
		try {
			$hosts = $provider->api('host.get', [
				'output' => ['hostid', 'host', 'name', 'status'],
				'search' => ['host' => $name, 'name' => $name],
				'searchByAny' => true,
				'limit' => 20,
			]);
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		if (empty($hosts)) {
			$this->stdout("Узлы по '$name' не найдены\n");
			return ExitCode::OK;
		}
		foreach ($hosts as $host) {
			$this->stdout(str_pad($host['hostid'], 10).str_pad($host['host'], 40)
				.$host['name'].(($host['status'] ?? '0') == 1 ? ' [отключен]' : '')."\n");
		}
		return ExitCode::OK;
	}

	/**
	 * Узел по hostid и его активные проблемы - то, что рисует панель.
	 * @param string $hostid
	 * @return int
	 */
	public function actionHost($hostid)
	{
		if (!$provider = $this->provider()) return ExitCode::CONFIG;
		try {
			$hosts = $provider->api('host.get', [
				'output' => ['hostid', 'host', 'name', 'status'],
				'hostids' => [$hostid],
			]);
			if (empty($hosts)) {
				$this->stdout("Узел $hostid не найден (или не виден токену)\n");
				return ExitCode::UNSPECIFIED_ERROR;
			}
			$this->stdout('Узел: '.$hosts[0]['host'].' ('.$hosts[0]['name'].")\n");

			$problems = $provider->api('problem.get', [
				'output' => ['name', 'severity', 'clock'],
				'hostids' => [$hostid],
				'recent' => false,
			]);
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		if (empty($problems)) {
			$this->stdout("Активных проблем нет\n");
			return ExitCode::OK;
		}
		foreach ($problems as $problem) {
			$severity = (int)($problem['severity'] ?? 0);
			$this->stdout(str_pad(ZabbixProvider::SEVERITIES[$severity] ?? (string)$severity, 20)
				.date('Y-m-d H:i', (int)$problem['clock']).'  '.$problem['name']."\n");
		}
		return ExitCode::OK;
	}

	/**
	 * Item'ы узла с последними значениями - чтобы понять, почему метрика
	 * не попала в панель (нестандартный ключ, нет истории, item отключён).
	 * Показывает, распознан ли ключ провайдером.
	 * @param string $hostid
	 * @param string $filter подстрока ключа (memory, cpu, vfs.fs, uptime);
	 *   пусто - только те ключи, которые ищет панель
	 * @return int
	 */
	public function actionItems($hostid, $filter = '')
	{
		if (!$provider = $this->provider()) return ExitCode::CONFIG;

		$params = [
			'output' => ['itemid', 'key_', 'name', 'value_type', 'status'],
			'hostids' => [$hostid],
			'sortfield' => 'key_',
			'limit' => 200,
		];
		$params['search'] = ['key_' => $filter !== '' ? $filter : [
			'system.uptime', 'cpu.util', 'vm.memory', 'vfs.fs.size[', 'vfs.fs.dependent.size[',
		]];
		if ($filter === '') $params['searchByAny'] = true;

		try {
			$items = $provider->api('item.get', $params) ?? [];
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		if (!$items) {
			$this->stdout("Item'ы не найдены\n");
			return ExitCode::OK;
		}

		$this->stdout(str_pad('распознан', 12).str_pad('значение', 14)."ключ / название\n");
		foreach ($items as $item) {
			$parsed = $provider->classifyItem((string)$item['key_']);
			$metric = $parsed ? $parsed['metric'].($parsed['invert'] ? ' (инв.)' : '') : '-';

			$value = '';
			try {
				$last = $provider->lastValue($item);
				$value = is_null($last) ? 'нет истории' : (string)round($last, 2);
			} catch (\Throwable $e) {
				$value = 'ошибка';
			}
			//отключённый item в панель не попадёт - панель берёт только monitored
			$disabled = ($item['status'] ?? '0') == 1 ? ' [ОТКЛЮЧЁН]' : '';

			$this->stdout(str_pad($metric, 12).str_pad($value, 14)
				.$item['key_'].$disabled.'  ('.$item['name'].")\n");
		}
		return ExitCode::OK;
	}

	/**
	 * Сквозная проверка на объекте ARMS: применимость, привязка из
	 * external_links и то, что реально покажет панель в карточке.
	 * @param string $classId kebab-case класс модели (comps, techs)
	 * @param int $id id объекта
	 * @return int
	 */
	public function actionObject($classId, $id)
	{
		if (!$provider = $this->provider()) return ExitCode::CONFIG;

		$class = DocsHelper::findDocClass($classId);
		if (!$class) {
			$this->stderr("Класс '$classId' не найден\n");
			return ExitCode::DATAERR;
		}
		/** @var ArmsModel $model */
		$model = $class::findOne($id);
		if (!is_object($model)) {
			$this->stderr("Объект $classId:$id не найден\n");
			return ExitCode::DATAERR;
		}
		$this->stdout('Объект: '.$model->name." ($classId:$id)\n");

		if (!$provider->appliesTo($model)) {
			$this->stdout("Провайдер к объекту неприменим - панели не будет\n");
			return ExitCode::OK;
		}
		$binding = $provider->binding($model);
		$this->stdout('Привязка (external_links '.ZabbixProvider::HOSTID_KEY.'): '
			.($binding ?: 'нет - панель будет искать узел по имени')."\n");

		try {
			$html = $provider->renderPanel(ZabbixProvider::PANEL, $model);
		} catch (\Throwable $e) {
			$this->stderr('ERROR: панель не отрисована: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout("--- панель ---\n");
		$text = html_entity_decode(strip_tags(preg_replace('/<(br|\/div|\/li|\/tr)[^>]*>/i', "\n", $html)));
		$this->stdout(trim(preg_replace('/\n\s*\n+/', "\n", $text))."\n");
		return ExitCode::OK;
	}
}
