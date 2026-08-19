<?php

namespace tests\unit\components\integrations;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\ZabbixProvider;
use app\components\integrations\providers\ZabbixSyncProvider;
use app\models\base\ArmsModel;
use app\models\Comps;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты провайдера «Постановка на мониторинг» (docs/dev/integrations.md):
 * применимость к ОС/оборудованию, рендер вердикта explain-режима
 * синхронизации arms.zabbix, краткий журнал совпавших правил и полный лог.
 * Запрос к explain.php подменяется в наследнике — сеть не используется.
 */
class ZabbixSyncProviderTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Провайдер с подменённым транспортом.
	 * @param array|\Throwable $report что вернёт fetchExplain
	 * @param ZabbixProvider|null $zabbix встроенный провайдер Zabbix
	 *   (подменяет поиск в реестре)
	 */
	private function makeProvider($report = [], array $config = [], ?ZabbixProvider $zabbix = null): ZabbixSyncProvider
	{
		$provider = new class($report, $zabbix) extends ZabbixSyncProvider {
			public $report;
			public array $calls = [];
			public ?ZabbixProvider $zabbix;

			public function __construct($report, ?ZabbixProvider $zabbix)
			{
				$this->report = $report;
				$this->zabbix = $zabbix;
			}

			protected function fetchExplain(string $class, int $id): array
			{
				$this->calls[] = [$class, $id];
				if ($this->report instanceof \Throwable) throw $this->report;
				return $this->report;
			}

			protected function embeddedZabbix(): ?ZabbixProvider
			{
				return $this->zabbix;
			}
		};
		$provider->id = 'zabbix-sync';
		$provider->config = array_merge([
			'url' => 'https://synchost/explain.php',
			'token' => 'test-token',
		], $config);
		return $provider;
	}

	/**
	 * Встраиваемый провайдер Zabbix с подменённым рендером: возвращает
	 * маркер (и признак компактного режима), в сеть не ходит
	 * @param string|null $binding привязка hostid (null - узла в Zabbix нет)
	 * @param string|\Throwable $html что вернёт renderPanel
	 */
	private function makeZabbix(?string $binding = '10501', $html = '<span id="zbx-live">live</span>'): ZabbixProvider
	{
		$zabbix = new class($binding, $html) extends ZabbixProvider {
			public ?string $bindingValue;
			public $html;

			public function __construct(?string $binding, $html)
			{
				$this->bindingValue = $binding;
				$this->html = $html;
			}

			public function binding(ArmsModel $model): ?string
			{
				return $this->bindingValue;
			}

			public function renderPanel(string $panelId, ArmsModel $model): string
			{
				if ($this->html instanceof \Throwable) throw $this->html;
				return $this->html.($this->compact ? '<!--compact-->' : '');
			}
		};
		$zabbix->id = 'zabbix';
		$zabbix->config = ['api' => 'https://zabbix.local/api', 'token' => 't', 'embedded' => true];
		return $zabbix;
	}

	/** Типовой отчет explain.php: узел добавляется, один именованный набор + один старого формата */
	private function sampleReport(array $override = []): array
	{
		return array_merge([
			'host' => ['class' => 'comps', 'id' => 42, 'name' => 'srv1.domain.local'],
			'verdict' => 'add',
			'errors' => [],
			'status' => 0,
			'sets' => [
				[
					'index' => 'Поддерживаемые ОС',	//именованный набор
					'desc' => null,
					'matched' => 1,
					'rules' => [
						['index' => 0, 'desc' => null, 'conditions' => 'type=comps, OS=Linux',
							'matched' => false, 'failedOn' => 'OS'],
						['index' => 1, 'desc' => 'Windows серверы', 'conditions' => 'type=comps, OS=Windows',
							'matched' => true, 'failedOn' => null],
					],
				],
				[
					'index' => 3,	//безымянный набор старого формата, ничего не совпало
					'desc' => null,
					'matched' => null,
					'rules' => [
						['index' => 0, 'desc' => null, 'conditions' => 'type=techs',
							'matched' => false, 'failedOn' => 'type'],
					],
				],
			],
			'actions' => [
				'actions' => ['update', 'create'],
				'templates' => ['Windows by Zabbix agent'],
				'groups' => ['Челябинск'],
			],
		], $override);
	}

	private function comp(int $id = 42): Comps
	{
		$comp = new Comps(['name' => 'SRV1']);
		$comp->id = $id;
		$comp->setIsNewRecord(false);
		return $comp;
	}

	/** Настроенность: нужны url и token */
	public function testIsConfigured()
	{
		$this->assertTrue($this->makeProvider()->isConfigured());

		$noToken = $this->makeProvider();
		$noToken->config = ['url' => 'x'];
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

	/** Привязка — id самого объекта (класс/id в терминах инвентори) */
	public function testBinding()
	{
		$provider = $this->makeProvider();
		$this->assertSame('comps/42', $provider->binding($this->comp()));

		$tech = new Techs();
		$tech->id = 7;
		$this->assertSame('techs/7', $provider->binding($tech));

		$this->assertNull($provider->binding(new Comps()));	//несохранённый объект
	}

	/** Вердикт add: зелёный бейдж, краткий журнал совпавших правил с именами */
	public function testRenderPanelAdd()
	{
		$provider = $this->makeProvider($this->sampleReport());
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('будет добавлен в мониторинг', $html);
		$this->assertStringContainsString('bg-success', $html);
		//бейдж - кнопка, открывающая модальное окно с журналом
		$this->assertStringContainsString('data-bs-toggle="modal"', $html);
		$this->assertStringContainsString('data-bs-target="#zabbix-sync-modal-comps-42"', $html);
		$this->assertStringContainsString('modal fade', $html);
		//краткий журнал (в модалке): имя набора и описание совпавшего правила
		$this->assertStringContainsString('Поддерживаемые ОС', $html);
		$this->assertStringContainsString('Windows серверы', $html);
		//запрошен именно этот узел
		$this->assertSame([['comps', 42]], $provider->calls);
	}

	/** Полный лог: пропущенные правила с условиями и на чем срезались, безымянные — по номерам */
	public function testRenderPanelFullLog()
	{
		$provider = $this->makeProvider($this->sampleReport());
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		//сворачиваемый блок «подробно» внутри модалки
		$this->assertStringContainsString('подробно', $html);
		$this->assertStringContainsString('data-bs-toggle="collapse"', $html);
		//пропущенное правило: условия и несработавшее условие
		$this->assertStringContainsString('type=comps, OS=Linux', $html);
		$this->assertStringContainsString('не прошло условие', $html);
		$this->assertStringContainsString('OS', $html);
		//безымянные набор/правило именуются номерами
		$this->assertStringContainsString('набор №3', $html);
		$this->assertStringContainsString('правило №0', $html);
		//набор без совпадений подписан
		$this->assertStringContainsString('ни одно правило не совпало', $html);
		//итоговые шаблоны/группы в сводке
		$this->assertStringContainsString('Windows by Zabbix agent', $html);
		$this->assertStringContainsString('Челябинск', $html);
	}

	/** Вердикт declined: причины отказа из правил показаны */
	public function testRenderPanelDeclined()
	{
		$provider = $this->makeProvider($this->sampleReport([
			'verdict' => 'declined',
			'errors' => ['эту ОС мониторить не умеем'],
		]));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('не ставится на мониторинг', $html);
		$this->assertStringContainsString('эту ОС мониторить не умеем', $html);
	}

	/** Вердикт monitored + status=1: узел в Zabbix есть, но приостановлен */
	public function testRenderPanelSuspended()
	{
		$provider = $this->makeProvider($this->sampleReport([
			'verdict' => 'monitored',
			'status' => 1,
		]));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('на мониторинге, но приостановлен', $html);
		$this->assertStringContainsString('bg-warning', $html);
	}

	/** Компактный режим (вложенные списки): только бейдж, без модалки и журнала */
	public function testRenderPanelCompact()
	{
		$provider = $this->makeProvider($this->sampleReport());
		$provider->compact = true;
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('будет добавлен в мониторинг', $html);
		$this->assertStringNotContainsString('modal', $html);
		$this->assertStringNotContainsString('подробно', $html);
		$this->assertStringNotContainsString('Поддерживаемые ОС', $html);
	}

	/** Ошибка транспорта поднимается исключением (ядро покажет заглушку) */
	public function testTransportErrorThrows()
	{
		$provider = $this->makeProvider(new \RuntimeException('Сервис синхронизации Zabbix недоступен'));
		$this->expectException(\RuntimeException::class);
		$provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());
	}

	/**
	 * Провайдер с подменённым HTTP-транспортом (fetchExplain настоящий):
	 * для проверки разбора ответа
	 */
	private function makeHttpProvider(string $body, int $status): ZabbixSyncProvider
	{
		$provider = new class([$body, $status]) extends ZabbixSyncProvider {
			public array $response;

			public function __construct(array $response)
			{
				$this->response = $response;
			}

			protected function httpGet(string $url): array
			{
				return $this->response;
			}
		};
		$provider->id = 'zabbix-sync';
		$provider->config = ['url' => 'https://synchost/explain.php', 'token' => 't'];
		return $provider;
	}

	/**
	 * Не-JSON ответ (HTML от Apache при 403/404) — в сообщении об ошибке
	 * виден HTTP-код и начало ответа: иначе по заглушке панели не понять,
	 * кто именно ответил
	 */
	public function testNonJsonResponseShowsStatusAndSnippet()
	{
		$provider = $this->makeHttpProvider(
			'<html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>', 403);
		try {
			$provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());
			$this->fail('ожидалось исключение');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('HTTP 403', $e->getMessage());
			$this->assertStringContainsString('Forbidden', $e->getMessage());
			$this->assertStringNotContainsString('<html>', $e->getMessage()); //теги вычищены
		}
	}

	/** JSON с ключом error (ответ самого explain.php) показывается как есть */
	public function testExplainErrorPassedThrough()
	{
		$provider = $this->makeHttpProvider('{"error":"invalid token"}', 403);
		try {
			$provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());
			$this->fail('ожидалось исключение');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('invalid token', $e->getMessage());
		}
	}

	/** Корректный JSON через настоящий fetchExplain разбирается */
	public function testHttpProviderParsesReport()
	{
		$provider = $this->makeHttpProvider(json_encode($this->sampleReport()), 200);
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());
		$this->assertStringContainsString('будет добавлен в мониторинг', $html);
	}

	/** TTL по умолчанию 0: вердикт дешёвый, обновляем при каждом открытии карточки */
	public function testPanelTtlDefaultZero()
	{
		$provider = $this->makeProvider();
		$this->assertSame(0, $provider->panelTtl(ZabbixSyncProvider::PANEL, new Comps()));

		$provider = $this->makeProvider([], ['cacheTtl' => 120]);
		$this->assertSame(120, $provider->panelTtl(ZabbixSyncProvider::PANEL, new Comps()));
	}

	/** Композиция: узел на мониторинге - под вердиктом живой Zabbix-блок */
	public function testEmbedZabbixWhenMonitored()
	{
		$provider = $this->makeProvider($this->sampleReport(['verdict' => 'monitored']),
			[], $this->makeZabbix(null)); //вердикта monitored достаточно и без привязки
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('на мониторинге', $html);
		$this->assertStringContainsString('zbx-live', $html);
	}

	/**
	 * Негативный вердикт без привязки: Zabbix-блока нет - исчезает
	 * дублирующее «узел не найден в Zabbix»
	 */
	public function testNoEmbedOnNegativeVerdictWithoutBinding()
	{
		$provider = $this->makeProvider($this->sampleReport(['verdict' => 'declined']),
			[], $this->makeZabbix(null));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('не ставится на мониторинг', $html);
		$this->assertStringNotContainsString('zbx-live', $html);
	}

	/**
	 * Негативный вердикт, но привязка hostid есть (узел заведён вручную,
	 * мимо правил синка): живой блок показываем
	 */
	public function testEmbedOnBindingDespiteNegativeVerdict()
	{
		$provider = $this->makeProvider($this->sampleReport(['verdict' => 'update-only']),
			[], $this->makeZabbix('10501'));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('не будет добавлен', $html);
		$this->assertStringContainsString('zbx-live', $html);
	}

	/** Вердикт add: узла в Zabbix ещё нет - блок не рисуем, бейдж говорит сам за себя */
	public function testNoEmbedOnAddWithoutBinding()
	{
		$provider = $this->makeProvider($this->sampleReport(), [], $this->makeZabbix(null));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('будет добавлен в мониторинг', $html);
		$this->assertStringNotContainsString('zbx-live', $html);
	}

	/** Компактный режим пробрасывается во встроенный провайдер */
	public function testEmbedPropagatesCompact()
	{
		$zabbix = $this->makeZabbix('10501');
		$provider = $this->makeProvider($this->sampleReport(['verdict' => 'monitored']), [], $zabbix);
		$provider->compact = true;
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('zbx-live', $html);
		$this->assertStringContainsString('<!--compact-->', $html);
		$this->assertTrue($zabbix->compact);
	}

	/**
	 * Половины падают независимо: недоступный Zabbix не прячет вердикт,
	 * вместо блока - заглушка в стиле ядра
	 */
	public function testZabbixFailureKeepsVerdict()
	{
		$provider = $this->makeProvider($this->sampleReport(['verdict' => 'monitored']),
			[], $this->makeZabbix('10501', new \RuntimeException('Zabbix API недоступен')));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('на мониторинге', $html);
		$this->assertStringNotContainsString('zbx-live', $html);
		//YII_DEBUG в тестах включён - заглушка с причиной
		$this->assertStringContainsString('Zabbix API недоступен', $html);
	}

	/**
	 * Обратная независимость: упавший explain при живой привязке Zabbix
	 * не роняет панель - вместо вердикта заглушка, живой блок на месте
	 */
	public function testExplainFailureKeepsZabbix()
	{
		$provider = $this->makeProvider(new \RuntimeException('Сервис синхронизации Zabbix недоступен'),
			[], $this->makeZabbix('10501'));
		$html = $provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());

		$this->assertStringContainsString('Сервис синхронизации Zabbix недоступен', $html);
		$this->assertStringContainsString('zbx-live', $html);
	}

	/**
	 * А без привязки падение explain по-прежнему поднимается исключением
	 * (ядро покажет заглушку) - Zabbix-блока всё равно не было бы
	 */
	public function testExplainFailureThrowsWithoutBinding()
	{
		$provider = $this->makeProvider(new \RuntimeException('недоступен'),
			[], $this->makeZabbix(null));
		$this->expectException(\RuntimeException::class);
		$provider->renderPanel(ZabbixSyncProvider::PANEL, $this->comp());
	}

	/**
	 * Поиск встроенного провайдера в реестре: только ZabbixProvider с
	 * 'embedded' => true в СВОЁМ конфиге; без флага композиции нет
	 * (обе карточки живут по-старому)
	 */
	public function testEmbeddedZabbixLookupRequiresFlag()
	{
		$savedParams = Yii::$app->params['integrations'] ?? null;
		IntegrationsRegistry::reset();
		try {
			$zabbixConfig = [
				'class' => ZabbixProvider::class,
				'api' => 'https://zabbix.local/api',
				'token' => 't',
			];
			$provider = new ZabbixSyncProvider();
			$provider->id = 'zabbix-sync';
			$provider->config = ['url' => 'x', 'token' => 'y'];
			$lookup = function () {
				/** @var ZabbixSyncProvider $this */
				return $this->embeddedZabbix();
			};

			//без флага embedded встраивания нет
			Yii::$app->params['integrations'] = ['zabbix' => $zabbixConfig];
			$this->assertNull($lookup->call($provider));

			//с флагом - находится, и карточка самого zabbix исчезает
			IntegrationsRegistry::reset();
			Yii::$app->params['integrations'] = ['zabbix' => $zabbixConfig + ['embedded' => true]];
			$embedded = $lookup->call($provider);
			$this->assertInstanceOf(ZabbixProvider::class, $embedded);
			$this->assertSame([], $embedded->panels(new Comps()));
		} finally {
			Yii::$app->params['integrations'] = $savedParams;
			IntegrationsRegistry::reset();
		}
	}
}
