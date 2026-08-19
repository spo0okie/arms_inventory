<?php

namespace tests\unit\components\integrations;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\PanelsCache;
use app\components\integrations\PanelsWidget;
use app\components\integrations\providers\ZabbixProvider;
use app\models\Comps;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты провайдера Zabbix (docs/dev/integrations.md): применимость
 * к ОС/оборудованию, привязка hostid из external_links с фолбэком поиска
 * по имени, рендер панели проблем. Zabbix JSON-RPC подменяется в
 * наследнике — сеть не используется.
 */
class ZabbixProviderTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Провайдер с подменённым транспортом.
	 * @param array $responses карта method => result (что вернёт zabbixCall);
	 *   значением может быть callable(params) - для history.get, где ответ
	 *   зависит от запрошенного itemid
	 */
	private function makeProvider(array $responses = [], array $config = []): ZabbixProvider
	{
		$provider = new class($responses) extends ZabbixProvider {
			public array $responses;
			public array $calls = [];

			public function __construct(array $responses)
			{
				$this->responses = $responses;
			}

			protected function zabbixCall(string $method, array $params): ?array
			{
				$this->calls[] = ['method' => $method, 'params' => $params];
				$response = $this->responses[$method] ?? [];
				return is_callable($response) ? $response($params) : $response;
			}
		};
		$provider->id = 'zabbix';
		$provider->config = array_merge([
			'api' => 'https://zabbix.local/api_jsonrpc.php',
			'token' => 'test-token',
		], $config);
		return $provider;
	}

	/** Настроенность: нужны api и token */
	public function testIsConfigured()
	{
		$this->assertTrue($this->makeProvider()->isConfigured());

		$noToken = $this->makeProvider();
		$noToken->config = ['api' => 'x'];
		$this->assertFalse($noToken->isConfigured());
	}

	/** Применим к ОС и оборудованию, не к пользователю */
	public function testAppliesTo()
	{
		$provider = $this->makeProvider();
		$this->assertTrue($provider->appliesTo(new Comps()));
		$this->assertTrue($provider->appliesTo(new Techs()));
		$this->assertFalse($provider->appliesTo(new Users()));
	}

	/** Привязка: hostid из external_links */
	public function testBindingFromExternalLinks()
	{
		$provider = $this->makeProvider();

		$comp = new Comps();
		$comp->external_links = json_encode(['Zabbix.hostid' => '10501']);
		$this->assertSame('10501', $provider->binding($comp));

		$this->assertNull($provider->binding(new Comps()));
	}

	/**
	 * Рендер панели по явной привязке: проблемы отсортированы, показаны
	 * важности и ссылка на узел; поиска по имени не было
	 */
	public function testRenderPanelWithBinding()
	{
		$provider = $this->makeProvider([
			'problem.get' => [
				['name' => 'High CPU', 'severity' => '4', 'clock' => (string)mktime(10, 0, 0, 2, 1, 2026)],
				['name' => 'Disk low', 'severity' => '2', 'clock' => (string)mktime(9, 0, 0, 2, 1, 2026)],
			],
		], ['web' => 'https://zabbix.local/zabbix']);

		$comp = new Comps(['name' => 'SRV1']);
		$comp->external_links = json_encode(['Zabbix.hostid' => '10501']);

		$html = $provider->renderPanel(ZabbixProvider::PANEL, $comp);

		$this->assertStringContainsString('2', $html); //счётчик проблем
		$this->assertStringContainsString('High CPU', $html);
		$this->assertStringContainsString('высокая', $html);
		$this->assertStringContainsString('Disk low', $html);
		$this->assertStringContainsString('hostid=10501', $html); //ссылка L0

		$methods = array_column($provider->calls, 'method');
		$this->assertContains('problem.get', $methods);
		//при явной привязке поиска узла по имени нет (host.get с filter);
		//host.get за состоянием узла (по hostids) - другое дело
		foreach ($provider->calls as $call) {
			if ($call['method'] !== 'host.get') continue;
			$this->assertArrayNotHasKey('filter', $call['params'], 'поиска по имени быть не должно');
		}
	}

	/** Признак состояния узла: включён ли мониторинг и доступен ли узел */
	public function testHostStateBadges()
	{
		$cases = [
			//интерфейсы, status узла => что должно быть в панели
			[[['available' => '1']], '0', 'доступен'],
			[[['available' => '2']], '0', 'недоступен'],
			[[['available' => '0']], '0', 'доступность неизвестна'],
			//явная недоступность важнее доступности другого канала
			[[['available' => '1'], ['available' => '2']], '0', 'недоступен'],
			//выключенный мониторинг важнее любой доступности
			[[['available' => '1']], '1', 'мониторинг выключен'],
		];
		foreach ($cases as [$interfaces, $status, $expected]) {
			$provider = $this->makeProvider([
				'problem.get' => [],
				'host.get' => [[
					'hostid' => '10501', 'host' => 'SRV1', 'name' => 'SRV1',
					'status' => $status, 'interfaces' => $interfaces,
				]],
			]);
			$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
			$this->assertStringContainsString($expected, $html);
		}
	}

	/** Активный агент (active_available) учитывается наравне с интерфейсами */
	public function testHostStateActiveAgent()
	{
		$provider = $this->makeProvider([
			'problem.get' => [],
			'host.get' => [[
				'hostid' => '10501', 'status' => '0',
				'interfaces' => [['available' => '0']], //пассивных проверок не было
				'active_available' => '1',
			]],
		]);
		$this->assertStringContainsString('доступен',
			$provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));
	}

	/**
	 * active_available появился в Zabbix 6.4: если API его не знает,
	 * запрос повторяется без этого поля, а не теряет состояние узла
	 */
	public function testHostStateFallbackForOldApi()
	{
		$provider = $this->makeProvider([
			'problem.get' => [],
			'host.get' => static function ($params) {
				if (in_array('active_available', $params['output'], true))
					throw new \RuntimeException('Zabbix API: Invalid parameter "/output/4"');
				return [['hostid' => '10501', 'status' => '0', 'interfaces' => [['available' => '1']]]];
			},
		]);
		$this->assertStringContainsString('доступен',
			$provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));
	}

	/** Сбой чтения состояния не роняет панель */
	public function testHostStateFailureKeepsPanel()
	{
		$provider = $this->makeProvider([
			'problem.get' => [],
			'host.get' => static function () { throw new \RuntimeException('timeout'); },
		]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('проблем нет', $html);
		$this->assertStringNotContainsString('доступен', $html);
	}

	/** Три ссылки в Zabbix: дашборд, последние данные, проблемы */
	public function testHostLinks()
	{
		$provider = $this->makeProvider(['problem.get' => []],
			['web' => 'https://zabbix.local/zabbix/']); //хвостовой слэш не должен удваиваться
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());

		//в html амперсанды экранированы
		$this->assertStringContainsString('/zabbix/zabbix.php?action=host.dashboard.view&amp;hostid=10501', $html);
		$this->assertStringContainsString('action=latest.view', $html);
		$this->assertStringContainsString('action=problem.view', $html);
		$this->assertStringContainsString('hostids%5B%5D=10501', $html);
		$this->assertStringNotContainsString('//zabbix.php', $html);
	}

	/** Без web ссылок нет (нечего показывать) */
	public function testHostLinksAbsentWithoutWeb()
	{
		$provider = $this->makeProvider(['problem.get' => []]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringNotContainsString('<a ', $html);
	}

	/**
	 * Сортировка проблем — своя, а не API: problem.get принимает в
	 * sortfield только eventid (иначе Invalid parameter "/sortfield/1").
	 * Порядок: важность убыв., внутри важности свежие сверху.
	 */
	public function testProblemsSortedLocallyNotByApi()
	{
		$old = mktime(8, 0, 0, 2, 1, 2026);
		$new = mktime(12, 0, 0, 2, 1, 2026);
		$provider = $this->makeProvider([
			'problem.get' => [
				['name' => 'Warn old', 'severity' => '2', 'clock' => (string)$old],
				['name' => 'Crit', 'severity' => '5', 'clock' => (string)$old],
				['name' => 'Warn new', 'severity' => '2', 'clock' => (string)$new],
			],
		]);
		$comp = new Comps();
		$comp->external_links = json_encode(['Zabbix.hostid' => '10501']);

		$html = $provider->renderPanel(ZabbixProvider::PANEL, $comp);

		//API-сортировка по severity недопустима: только скалярный eventid
		$problemGet = null;
		foreach ($provider->calls as $call) if ($call['method'] === 'problem.get') $problemGet = $call;
		$this->assertSame('eventid', $problemGet['params']['sortfield']);

		$order = [strpos($html, 'Crit'), strpos($html, 'Warn new'), strpos($html, 'Warn old')];
		$this->assertSame($order, array_values(array_filter($order, 'is_int')));
		$this->assertTrue($order[0] < $order[1] && $order[1] < $order[2], 'порядок: важность, затем свежесть');
	}

	/** Нет проблем — панель показывает OK */
	public function testRenderPanelNoProblems()
	{
		$provider = $this->makeProvider(['problem.get' => []]);
		$comp = new Comps();
		$comp->external_links = json_encode(['Zabbix.hostid' => '1']);

		$html = $provider->renderPanel(ZabbixProvider::PANEL, $comp);
		$this->assertStringContainsString('OK', $html);
		$this->assertStringContainsString('проблем нет', $html);
	}

	/**
	 * Нет привязки — фолбэк-поиск по имени: host.get вызывается с
	 * техническими именами, найденный hostid используется для проблем
	 */
	public function testRenderPanelSearchFallback()
	{
		$provider = $this->makeProvider([
			'host.get' => [['hostid' => '20502']],
			'problem.get' => [],
		]);

		$comp = new Comps(['name' => 'SRV2']);
		//fqdn вычисляется из name+domain; здесь достаточно name
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $comp);

		$this->assertStringContainsString('проблем нет', $html);

		$hostGet = null;
		foreach ($provider->calls as $call)
			if ($call['method'] === 'host.get' && isset($call['params']['filter'])) $hostGet = $call;
		$this->assertNotNull($hostGet, 'должен быть поиск по имени');
		$this->assertContains('SRV2', $hostGet['params']['filter']['host']);
		//проблемы запрошены по найденному hostid
		$problemGet = null;
		foreach ($provider->calls as $call) if ($call['method'] === 'problem.get') $problemGet = $call;
		$this->assertSame(['20502'], $problemGet['params']['hostids']);
	}

	/** Узел не найден ни по привязке, ни по имени */
	public function testRenderPanelHostNotFound()
	{
		$provider = $this->makeProvider(['host.get' => []]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, new Comps(['name' => 'ghost']));
		$this->assertStringContainsString('не найден в Zabbix', $html);
	}

	/** Ошибка Zabbix API поднимается исключением (ядро покажет заглушку) */
	public function testApiErrorThrows()
	{
		$provider = new class extends ZabbixProvider {
			protected function zabbixCall(string $method, array $params): ?array
			{
				throw new \RuntimeException('Zabbix API: not authorised');
			}
		};
		$provider->id = 'zabbix';
		$provider->config = ['api' => 'x', 'token' => 'y'];

		$comp = new Comps();
		$comp->external_links = json_encode(['Zabbix.hostid' => '1']);

		$this->expectException(\RuntimeException::class);
		$provider->renderPanel(ZabbixProvider::PANEL, $comp);
	}

	/**
	 * Провайдер с метриками: item.get отдаёт заданные ключи, history.get -
	 * значение по itemid (индекс в списке ключей)
	 * @param array $keys [key_ => значение последнего замера]
	 * @param array $config конфиг провайдера
	 * @param int $age возраст последнего замера, сек
	 */
	private function makeMetricsProvider(array $keys, array $config = [], int $age = 60): ZabbixProvider
	{
		$clock = time() - $age;
		$items = [];
		$values = [];
		$index = 0;
		foreach ($keys as $key => $value) {
			$itemid = (string)++$index;
			$items[] = ['itemid' => $itemid, 'key_' => $key, 'value_type' => '0'];
			$values[$itemid] = $value;
		}
		return $this->makeProvider([
			'problem.get' => [],
			'item.get' => $items,
			'history.get' => static function ($params) use ($values, $clock) {
				$itemid = (string)$params['itemids'][0];
				return isset($values[$itemid])
					? [['value' => (string)$values[$itemid], 'clock' => (string)$clock]]
					: [];
			},
		], $config);
	}

	private function boundComp(): Comps
	{
		$comp = new Comps(['name' => 'SRV1']);
		$comp->external_links = json_encode(['Zabbix.hostid' => '10501']);
		return $comp;
	}

	/**
	 * Ключи штатных шаблонов «... by Zabbix agent» у Windows и Linux
	 * совпадают - метрики читаются без различения ОС
	 */
	public function testMetricsStandardKeys()
	{
		$provider = $this->makeMetricsProvider([
			'system.uptime' => 3600 * 24 * 5 + 3600 * 3, //5 дней 3 часа
			'system.cpu.util' => 37.4,
			'vm.memory.utilization' => 61,
			'vfs.fs.size[C:,pused]' => 92.5,	//Windows
			'vfs.fs.size[/,pused]' => 12,		//Linux
		]);

		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());

		$this->assertStringContainsString('5 дн 3 ч', $html);
		$this->assertStringContainsString('37.4%', $html);
		$this->assertStringContainsString('61%', $html);
		$this->assertStringContainsString('C:', $html);
		$this->assertStringContainsString('92.5%', $html);
		$this->assertStringContainsString('12%', $html);
	}

	/** «Обратные» ключи старых шаблонов пересчитываются в загрузку */
	public function testMetricsInvertedKeys()
	{
		$provider = $this->makeMetricsProvider([
			'system.cpu.util[,idle]' => 93,
			'vm.memory.size[pavailable]' => 30,
			'vfs.fs.dependent.size[/var,pfree]' => 10,
		]);

		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());

		$this->assertStringContainsString('7%', $html);	//100-93 загрузка CPU
		$this->assertStringContainsString('70%', $html);	//100-30 память
		$this->assertStringContainsString('90%', $html);	//100-10 /var
	}

	/**
	 * Готового процента памяти в шаблоне нет - считаем из байтов
	 * (total + available либо total + used)
	 */
	public function testMemoryFromBytes()
	{
		$fromAvailable = $this->makeMetricsProvider([
			'vm.memory.size[total]' => 8 * 1024 * 1024 * 1024,
			'vm.memory.size[available]' => 2 * 1024 * 1024 * 1024,
		]);
		$this->assertStringContainsString('75%',
			$fromAvailable->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));

		$fromUsed = $this->makeMetricsProvider([
			'vm.memory.size[total]' => 1000,
			'vm.memory.size[used]' => 400,
		]);
		$this->assertStringContainsString('40%',
			$fromUsed->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));
	}

	/** Готовый процент памяти важнее байтов - лишних запросов не делаем */
	public function testMemoryPercentWinsOverBytes()
	{
		$provider = $this->makeMetricsProvider([
			'vm.memory.utilization' => 61,
			'vm.memory.size[total]' => 1000,
			'vm.memory.size[available]' => 900, //дало бы 10%
		]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('61%', $html);
		$this->assertStringNotContainsString('10%', $html);
	}

	/** Одного total мало - метрика не рисуется */
	public function testMemoryTotalAloneIsNotEnough()
	{
		$provider = $this->makeMetricsProvider(['vm.memory.size[total]' => 1000]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringNotContainsString('Память', $html);
	}

	/** Свежие данные помечены временем последнего замера */
	public function testMetricsFreshness()
	{
		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37], [], 60);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		//формулировка - дело вёрстки, проверяем сам возраст данных
		$this->assertStringContainsString(
			Yii::$app->formatter->asRelativeTime(time() - 60), $html);
		$this->assertStringNotContainsString('устарели', $html);
	}

	/**
	 * Данные сняты давно (машина выключена) - метрики показываются, но
	 * помечены как устаревшие: иначе их можно принять за актуальные
	 */
	public function testMetricsStaleWarning()
	{
		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37], [], 7200);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('данные устарели', $html);
		$this->assertStringContainsString('37%', $html); //само значение остаётся
	}

	/** Порог устаревания настраивается */
	public function testStaleThresholdConfigurable()
	{
		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37], ['staleAfter' => 86400], 7200);
		$this->assertStringNotContainsString('устарели',
			$provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));
	}

	/** Item'ы есть, а значений нет вовсе - сбор данных прекращён */
	public function testMetricsNoDataAtAll()
	{
		$provider = $this->makeProvider([
			'problem.get' => [],
			'item.get' => [['itemid' => '1', 'key_' => 'system.cpu.util', 'value_type' => '0']],
			'history.get' => [],
		]);
		$this->assertStringContainsString('данные не поступали более суток',
			$provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));
	}

	/** Узел без подходящих item'ов - о свежести говорить нечего */
	public function testNoCandidatesNoFreshnessNote()
	{
		$provider = $this->makeProvider(['problem.get' => [], 'item.get' => []]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringNotContainsString('данные', $html);
	}

	/** Прямой item выигрывает у «обратного», если есть оба */
	public function testMetricsPrefersDirectItem()
	{
		$provider = $this->makeMetricsProvider([
			'system.cpu.util[,idle]' => 93,
			'system.cpu.util' => 25,
		]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('25%', $html);
		$this->assertStringNotContainsString('7%', $html);
	}

	/** Нет истории (item есть, данных за сутки нет) - метрика не рисуется */
	public function testMetricsWithoutHistorySkipped()
	{
		$provider = $this->makeProvider([
			'problem.get' => [],
			'item.get' => [['itemid' => '1', 'key_' => 'system.cpu.util', 'value_type' => '0']],
			'history.get' => [],
		]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringNotContainsString('CPU', $html);
		$this->assertStringContainsString('проблем нет', $html);
	}

	/** Сбой чтения метрик не роняет панель: проблемы всё равно видны */
	public function testMetricsFailureKeepsProblems()
	{
		$provider = $this->makeProvider([
			'problem.get' => [['name' => 'High CPU', 'severity' => '4', 'clock' => '1770000000']],
			'item.get' => static function () { throw new \RuntimeException('no permissions for history'); },
		]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('High CPU', $html);
	}

	/** Метрики отключаются конфигом (тогда item.get не запрашивается) */
	public function testMetricsDisabledByConfig()
	{
		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37], ['metrics' => false]);
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringNotContainsString('37%', $html);
		$this->assertNotContains('item.get', array_column($provider->calls, 'method'));
	}

	/**
	 * Компактный режим виджета - для вложенных списков (ОС внутри АРМ в
	 * карточке сотрудника): заголовок остаётся, контейнер плотнее (mb-2),
	 * кнопки действий не выводятся.
	 * Виджет в любом режиме в Zabbix не ходит - только кэш + ajax.
	 */
	public function testPanelsWidgetCompact()
	{
		Yii::$app->params['integrations'] = ['zabbix' => [
			'class' => ZabbixProvider::class,
			'api' => 'https://zabbix.local/api_jsonrpc.php',
			'token' => 'test-token',
		]];
		IntegrationsRegistry::reset();
		try {
			$comp = new Comps(['name' => 'SRV1']);
			$comp->id = 999001;
			$comp->setIsNewRecord(false);
			$comp->external_links = json_encode(['Zabbix.hostid' => '999000111']);

			$full = PanelsWidget::widget(['model' => $comp]);
			$compact = PanelsWidget::widget(['model' => $comp, 'compact' => true]);

			$this->assertStringContainsString('<h4>', $full);
			$this->assertStringContainsString('<h4>', $compact); //заголовок остаётся и в компакте
			//компакт отличается плотностью контейнера
			$this->assertStringContainsString('class="mb-3"', $full);
			$this->assertStringContainsString('class="mb-2"', $compact);
			//данные подтянутся ajax'ом через proxy (в json слэши экранированы)
			$this->assertStringContainsString('integrations/panel',
				str_replace('\\/', '/', $compact));
			//режим доезжает до proxy - иначе он отрендерит обычную панель
			$this->assertStringContainsString('compact=1', $compact);
			$this->assertStringNotContainsString('compact=1', $full);
		} finally {
			Yii::$app->params['integrations'] = [];
			IntegrationsRegistry::reset();
		}
	}

	/**
	 * Режим рендера доезжает до view провайдера ($compact): во вложенном
	 * списке метрики жмутся плотнее (pe-3 вместо pe-4)
	 */
	public function testCompactReachesView()
	{
		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37]);
		$this->assertStringContainsString('pe-4',
			$provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp()));

		$provider = $this->makeMetricsProvider(['system.cpu.util' => 37]);
		$provider->compact = true;
		$html = $provider->renderPanel(ZabbixProvider::PANEL, $this->boundComp());
		$this->assertStringContainsString('pe-3', $html);
		$this->assertStringNotContainsString('pe-4', $html);
	}

	/** У режимов разный HTML - и кэш у них раздельный */
	public function testCompactCachedSeparately()
	{
		$this->assertNotSame(
			PanelsCache::path('zabbix', ZabbixProvider::PANEL, '10501'),
			PanelsCache::path('zabbix', ZabbixProvider::PANEL, '10501', true)
		);
	}

	/** Панель объявлена с TTL из конфига */
	public function testPanelTtl()
	{
		$provider = $this->makeProvider([], ['cacheTtl' => 45]);
		$this->assertSame(45, $provider->panelTtl(ZabbixProvider::PANEL, new Comps()));
	}

	/**
	 * Встроенный режим ('embedded' => true): своей карточки нет - её
	 * содержимое рисует панель zabbix-sync; сам renderPanel при этом
	 * работает (композиция зовёт его напрямую)
	 */
	public function testEmbeddedHidesOwnPanel()
	{
		$provider = $this->makeProvider();
		$this->assertFalse($provider->isEmbedded());
		$this->assertArrayHasKey(ZabbixProvider::PANEL, $provider->panels(new Comps()));

		$embedded = $this->makeProvider([], ['embedded' => true]);
		$this->assertTrue($embedded->isEmbedded());
		$this->assertSame([], $embedded->panels(new Comps()));

		$comp = new Comps(['name' => 'SRV1']);
		$comp->external_links = json_encode(['Zabbix.hostid' => '10501']);
		$html = $embedded->renderPanel(ZabbixProvider::PANEL, $comp);
		$this->assertStringContainsString('проблем нет', $html);
	}
}
