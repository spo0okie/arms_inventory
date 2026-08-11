<?php

namespace tests\unit\components\integrations;

use app\components\integrations\providers\ZabbixProvider;
use app\models\Comps;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;

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
	 * @param array $responses карта method => result (что вернёт zabbixCall)
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
				return $this->responses[$method] ?? [];
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

		//при явной привязке host.get (поиск) не вызывается
		$methods = array_column($provider->calls, 'method');
		$this->assertContains('problem.get', $methods);
		$this->assertNotContains('host.get', $methods);
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
		foreach ($provider->calls as $call) if ($call['method'] === 'host.get') $hostGet = $call;
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

	/** Панель объявлена с TTL из конфига */
	public function testPanelTtl()
	{
		$provider = $this->makeProvider([], ['cacheTtl' => 45]);
		$this->assertSame(45, $provider->panelTtl(ZabbixProvider::PANEL, new Comps()));
	}
}
