<?php

namespace tests\unit\components\integrations;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\HttpTemplateProvider;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты декларативного HTTP-провайдера (docs/dev/integrations.md)
 * и шаблона панели телефонии ast22-phones. Внешняя ИС имитируется
 * data://-URL с готовым JSON — в сеть тесты не ходят.
 */
class HttpTemplateProviderTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var array исходные params для восстановления */
	private $originalIntegrations;

	protected function _before()
	{
		$this->originalIntegrations = Yii::$app->params['integrations'] ?? [];
		IntegrationsRegistry::reset();
	}

	protected function _after()
	{
		Yii::$app->params['integrations'] = $this->originalIntegrations;
		IntegrationsRegistry::reset();
	}

	/** Провайдер с конфигом без реестра (для прямых юнит-проверок) */
	private function makeProvider(array $config): HttpTemplateProvider
	{
		$provider = new HttpTemplateProvider();
		$provider->id = 'test-http';
		$provider->config = $config;
		return $provider;
	}

	/** data://-URL, отдающий заданный JSON */
	private function jsonUrl(array $data): string
	{
		return 'data://text/plain;base64,'.base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
	}

	/** Конфиг обязан содержать request, применимый класс и панель */
	public function testIsConfigured()
	{
		$valid = [
			'request' => 'https://x/{binding}',
			'appliesTo' => ['model' => Users::class],
			'panel' => ['title' => 'X'],
		];
		$this->assertTrue($this->makeProvider($valid)->isConfigured());

		foreach (['request', 'appliesTo', 'panel'] as $key) {
			$broken = $valid;
			unset($broken[$key]);
			$this->assertFalse($this->makeProvider($broken)->isConfigured(), "без $key");
		}

		$broken = $valid;
		$broken['appliesTo']['model'] = 'app\\models\\NoSuchClass';
		$this->assertFalse($this->makeProvider($broken)->isConfigured());
	}

	/** Применимость: класс модели + опциональный булев атрибут-условие */
	public function testAppliesTo()
	{
		$provider = $this->makeProvider([
			'appliesTo' => ['model' => Users::class, 'attribute' => 'Mobile'],
		]);

		$this->assertTrue($provider->appliesTo(new Users(['Mobile' => '79991234567'])));
		$this->assertFalse($provider->appliesTo(new Users()), 'условие-атрибут пусто');
		$this->assertFalse($provider->appliesTo(new Techs()), 'чужой класс');

		$noCondition = $this->makeProvider(['appliesTo' => ['model' => Users::class]]);
		$this->assertTrue($noCondition->appliesTo(new Users()), 'без условия достаточно класса');
	}

	/** Привязка: шаблон из атрибутов модели, пустой результат = null */
	public function testBinding()
	{
		$provider = $this->makeProvider(['binding' => '{Mobile}']);
		$this->assertSame('79991234567', $provider->binding(new Users(['Mobile' => '79991234567'])));
		$this->assertNull($provider->binding(new Users()));
	}

	/** Рендер без template: плоский список ключ-значение из JSON-ответа */
	public function testRenderPanelFlat()
	{
		$provider = $this->makeProvider([
			'binding' => '{Mobile}',
			'request' => $this->jsonUrl(['state' => 'online', 'nested' => ['rtt' => 5]]),
			'panel' => ['title' => 'X'],
		]);

		$html = $provider->renderPanel(HttpTemplateProvider::PANEL, new Users(['Mobile' => '79991234567']));
		$this->assertStringContainsString('online', $html);
		$this->assertStringContainsString('nested.rtt', $html);
		$this->assertStringContainsString('5', $html);
	}

	/** Объект применим, но привязка пустая - панель сообщает об этом без запроса */
	public function testRenderPanelNoBinding()
	{
		$provider = $this->makeProvider([
			'binding' => '{Mobile}',
			'request' => 'https://never-called.local/{binding}',
			'panel' => ['title' => 'X'],
		]);
		$this->assertStringContainsString('нет привязки',
			$provider->renderPanel(HttpTemplateProvider::PANEL, new Users()));
	}

	/** Некорректный ответ внешней ИС - исключение (ядро покажет заглушку) */
	public function testRenderPanelBadResponse()
	{
		$provider = $this->makeProvider([
			'binding' => '{Mobile}',
			'request' => 'data://text/plain,not-a-json',
			'panel' => ['title' => 'X'],
		]);
		$this->expectException(\RuntimeException::class);
		$provider->renderPanel(HttpTemplateProvider::PANEL, new Users(['Mobile' => '79991234567']));
	}

	/**
	 * Шаблон панели телефонии (providers/views/pbx/status.php) на ответе
	 * ast22-phones: прогрессивный бейдж, 4 лампочки, IP+модель из контакта,
	 * кнопка Web-UI, дублирование вызова
	 */
	public function testPbxTemplate()
	{
		$response = [
			'success' => true,
			'data' => [
				'subscriber' => ['id' => 7, 'extension' => '1001'],
				'status' => [
					'extension' => '1001',
					'configured' => true,
					'loaded' => true,
					'registered' => true,
					'online' => true,
					'device_state' => 'Not in use',
					'contacts' => [[
						'uri' => 'sip:1001@10.0.0.5:5060',
						'user_agent' => 'Yealink SIP-T31G',
						'rtt_ms' => 3.5,
					]],
					'error' => null,
				],
				'call_duplications' => [
					['dubler_number' => '555', 'delay_seconds' => 10, 'schedule' => 'Рабочее время'],
				],
			],
		];

		$provider = $this->makeProvider([
			'binding' => '{Mobile}',
			'request' => $this->jsonUrl($response),
			'web' => 'https://phones.local',
			'panel' => [
				'title' => 'Телефония',
				'template' => '@app/components/integrations/providers/views/pbx/status.php',
			],
		]);

		//шаблон обращается к $model->phone только в ветке "не найден",
		//поэтому Users с Mobile здесь достаточно
		$html = $provider->renderPanel(HttpTemplateProvider::PANEL, new Users(['Mobile' => '1001']));

		$this->assertStringContainsString('онлайн', $html);           //прогрессивный бейдж
		$this->assertStringContainsString('10.0.0.5', $html);          //IP из contact URI
		$this->assertStringContainsString('Yealink SIP-T31G', $html);  //модель
		$this->assertStringContainsString('Онлайн (qualify)', $html);  //подпись лампочки
		$this->assertStringContainsString('phones.local/subscriber/view?id=7', $html); //кнопка Web-UI
		$this->assertStringContainsString('555', $html);               //переадресация
		$this->assertStringContainsString('Рабочее время', $html);
	}

	/**
	 * Прогрессивный бейдж останавливается на первой недостигнутой ступени:
	 * есть в БД и Asterisk, но не зарегистрирован
	 */
	public function testPbxTemplateProgressiveBadge()
	{
		$response = [
			'success' => true,
			'data' => [
				'subscriber' => ['id' => 3, 'extension' => '200'],
				'status' => [
					'configured' => true,
					'loaded' => true,
					'registered' => false,
					'online' => false,
					'contacts' => [],
				],
				'call_duplications' => [],
			],
		];

		$provider = $this->makeProvider([
			'binding' => '{Mobile}',
			'request' => $this->jsonUrl($response),
			'panel' => ['template' => '@app/components/integrations/providers/views/pbx/status.php'],
		]);

		$html = $provider->renderPanel(HttpTemplateProvider::PANEL, new Users(['Mobile' => '200']));
		$this->assertStringContainsString('не зарегистрирован', $html);
		//web не задан - кнопки Web-UI нет
		$this->assertStringNotContainsString('subscriber/view', $html);
	}

	/** Через реестр: панель объявлена, ttl из конфига панели */
	public function testViaRegistry()
	{
		Yii::$app->params['integrations'] = [
			'pbx' => [
				'class' => HttpTemplateProvider::class,
				'title' => 'Телефония',
				'appliesTo' => ['model' => Users::class],
				'binding' => '{Mobile}',
				'request' => $this->jsonUrl(['ok' => true]),
				'panel' => ['title' => 'Телефония', 'ttl' => 30],
			],
		];
		IntegrationsRegistry::reset();

		$provider = IntegrationsRegistry::provider('pbx');
		$this->assertNotNull($provider);
		$this->assertSame('Телефония', $provider->getTitle());

		$user = new Users(['Mobile' => '1001']);
		$panels = $provider->panels($user);
		$this->assertArrayHasKey(HttpTemplateProvider::PANEL, $panels);
		$this->assertSame(30, $provider->panelTtl(HttpTemplateProvider::PANEL, $user));
	}
}
