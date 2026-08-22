<?php

namespace tests\unit\components\integrations;

use app\components\integrations\providers\MacSearchProvider;
use app\models\Comps;
use app\models\Manufacturers;
use app\models\Places;
use app\models\Ports;
use app\models\TechModels;
use app\models\Techs;
use app\models\TechTypes;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты провайдера «Порт коммутатора» (docs/dev/integrations.md, issue #218).
 *
 * Провайдер собирает состав опроса сам (коммутаторы инвентаризации по
 * площадке объекта), отдаёт его сервису arms.macsearch и размечает ответ
 * по связям портов. Здесь проверяется всё это, кроме сети: HTTP-запрос
 * подменяется в наследнике.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class MacSearchProviderTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
	}

	protected function _after()
	{
		Yii::$app->request->setQueryParams([]);
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/**
	 * Провайдер с подменённым транспортом.
	 * @param array $responses очередь ответов httpPost: [тело, HTTP-код] либо \Throwable
	 */
	private function makeProvider(array $responses = [], array $config = []): MacSearchProvider
	{
		$provider = new class($responses) extends MacSearchProvider {
			public array $responses;
			public array $requests = [];

			public function __construct(array $responses)
			{
				$this->responses = $responses;
			}

			protected function httpPost(string $url, string $body): array
			{
				$this->requests[] = ['url' => $url, 'body' => json_decode($body, true)];
				$response = array_shift($this->responses);
				if ($response instanceof \Throwable) throw $response;
				return $response ?? [json_encode(['status' => 'done', 'rows' => []]), 200];
			}
		};
		$provider->id = 'macsearch';
		$provider->config = array_merge([
			'url' => 'http://macsearch.local:8088',
			'token' => 'test-token',
		], $config);
		return $provider;
	}

	/** Ответ сервиса: адрес найден на одном порту */
	private function payload(array $rows, array $override = []): array
	{
		return array_merge([
			'status' => 'done',
			'job' => '104233-7',
			'mac' => '00:11:22:33:44:55',
			'mode' => 'lookup',
			'cached' => false,
			'duration' => 4.2,
			'targets' => ['requested' => 1, 'answered' => 1, 'failed' => 0],
			'rows' => $rows,
			'errors' => [],
		], $override);
	}

	private function response(array $payload, int $status = 200): array
	{
		return [json_encode($payload), $status];
	}

	private function comp(string $mac = '001122334455', int $id = 42): Comps
	{
		$comp = new Comps(['name' => 'SRV1', 'mac' => $mac]);
		$comp->id = $id;
		$comp->setIsNewRecord(false);
		return $comp;
	}

	/** Тип оборудования «Коммутатор» (в дампе он есть, но не будем на это полагаться) */
	private function switchType(): TechTypes
	{
		$type = TechTypes::findOne(['code' => 'net_switch']);
		if (is_object($type)) return $type;

		$type = new TechTypes();
		$type->setAttributes(['code' => 'net_switch', 'name' => 'Коммутатор', 'comment' => ''], false);
		$this->assertTrue($type->save(false));
		return $type;
	}

	/** Коммутатор в инвентаризации: модель нужного типа, вендор, IP, помещение */
	private function makeSwitch(array $attrs, string $vendor = 'Cisco'): Techs
	{
		$manufacturer = new Manufacturers();
		$manufacturer->setAttributes(['name' => $vendor.' '.uniqid(), 'comment' => ''], false);
		$this->assertTrue($manufacturer->save(false));

		$model = new TechModels();
		$model->setAttributes([
			'name' => 'Модель ' . uniqid(),
			'type_id' => $this->switchType()->id,
			'manufacturers_id' => $manufacturer->id,
			'comment' => '',
		], false);
		$this->assertTrue($model->save(false));

		$tech = new Techs();
		$tech->setAttributes(array_merge([
			'model_id' => $model->id,
			'num' => 'SW-'.uniqid(),
			'history' => '',
		], $attrs), false);
		$this->assertTrue($tech->save(false));

		//вендор в целях берётся из связи - сбрасываем кэш связей после ручного save
		$tech->refresh();
		return $tech;
	}

	private function makePlace(?int $parentId = null): Places
	{
		$place = new Places();
		$place->setAttributes([
			'name' => 'Площадка '.uniqid(), 'short' => substr(uniqid(), -6),
			'parent_id' => $parentId, 'comment' => '',
		], false);
		$this->assertTrue($place->save(false));
		return $place;
	}

	// --- базовое ---------------------------------------------------------

	/** Настроенность: нужны адрес сервиса и токен */
	public function testIsConfigured()
	{
		$this->assertTrue($this->makeProvider()->isConfigured());

		$noToken = $this->makeProvider();
		$noToken->config = ['url' => 'http://macsearch.local:8088'];
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

	/** Адреса объекта: одиночные, без дублей; диапазоны (issue #120) пропускаются */
	public function testMacs()
	{
		$provider = $this->makeProvider();
		$comp = $this->comp("001122334455\n00:11:22:33:44:66\n001122334455\n"
			."001122334400-0011223344ff\n000000000000");

		$this->assertSame(['001122334455', '001122334466'], $provider->macs($comp));
		//в привязке (ключе кэша) ещё и область опроса - см. testScopeFromRequest
		$this->assertSame('001122334455,001122334466@place', $provider->binding($comp));
		$this->assertNull($provider->binding($this->comp('')));
	}

	/**
	 * Панель в карточке сама не рисуется: опрос коммутаторов дорогой и
	 * запускается по клику иконки рядом с адресом
	 */
	public function testPanelIsNotAutomatic()
	{
		$panel = $this->makeProvider()->panels($this->comp())[MacSearchProvider::PANEL];
		$this->assertFalse($panel['auto']);

		//инстанс может вернуть автопанель обратно конфигом
		$auto = $this->makeProvider([], ['autoPanel' => true])->panels($this->comp());
		$this->assertTrue($auto[MacSearchProvider::PANEL]['auto']);
	}

	/**
	 * Иконка зовёт панель для ОДНОГО адреса: опрашиваем только его, и он же
	 * попадает в привязку (ключ кэша), чтобы результат не смешался с опросом
	 * всех адресов объекта
	 */
	public function testRequestedMacNarrowsSearch()
	{
		$provider = $this->makeProvider();
		$comp = $this->comp("001122334455\n001122334466");

		Yii::$app->request->setQueryParams(['mac' => '00:11:22:33:44:66']);
		$this->assertSame(['001122334466'], $provider->requestedMacs($comp));
		$this->assertSame('001122334466@place', $provider->binding($comp));

		//чужой адрес игнорируем: панель не должна превращаться в свободный сканер
		Yii::$app->request->setQueryParams(['mac' => 'aabbccddeeff']);
		$this->assertSame(['001122334455', '001122334466'], $provider->requestedMacs($comp));
	}

	/**
	 * Область опроса выбирается пунктом меню: по умолчанию площадка объекта,
	 * но железку могли перевезти — тогда «по всем площадкам». Разные области
	 * не должны смешиваться в кэше, поэтому область входит в привязку.
	 */
	public function testScopeFromRequest()
	{
		$provider = $this->makeProvider();
		$comp = $this->comp('001122334455');

		$this->assertSame(MacSearchProvider::SCOPE_PLACE, $provider->requestedScope());
		$this->assertStringEndsWith('@place', $provider->binding($comp));

		Yii::$app->request->setQueryParams(['scope' => 'all']);
		$this->assertSame(MacSearchProvider::SCOPE_ALL, $provider->requestedScope());
		$this->assertStringEndsWith('@all', $provider->binding($comp));

		//мусор в параметре не меняет умолчание конфига
		Yii::$app->request->setQueryParams(['scope' => 'вся-вселенная']);
		$this->assertSame(MacSearchProvider::SCOPE_ALL,
			$this->makeProvider([], ['scope' => 'all'])->requestedScope());
	}

	/** Область опроса пишется в заголовок результата: иначе не понять, где искали */
	public function testScopeTitle()
	{
		$place = $this->makePlace();
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);
		$provider = $this->makeProvider();

		$this->assertSame($place->name, $provider->scopeTitle($switch));

		Yii::$app->request->setQueryParams(['scope' => 'all']);
		$this->assertSame('все площадки', $provider->scopeTitle($switch));
	}

	// --- сбор целей ------------------------------------------------------

	/** Цели опроса: коммутаторы с IP; без адреса и архивные не опрашиваются */
	public function testTargets()
	{
		$place = $this->makePlace();
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id], 'Cisco');
		$this->makeSwitch(['ip' => '', 'places_id' => $place->id]);         // без адреса
		$this->makeSwitch(['ip' => 'не адрес', 'places_id' => $place->id]); // адрес не IP

		$targets = $this->makeProvider()->targets([$place->id]);

		$this->assertCount(1, $targets);
		$this->assertSame($switch->id, $targets[0]['id']);
		$this->assertSame('10.50.2.16', $targets[0]['host']);
		$this->assertStringContainsString('Cisco', $targets[0]['vendor']);
		$this->assertNotEmpty($targets[0]['model']);
	}

	/** Область опроса: площадка объекта — вся её ветка помещений */
	public function testTargetsByObjectSite()
	{
		$site = $this->makePlace();
		$room = $this->makePlace($this->makePlace($site->id)->id);   // внук корня
		$near = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $room->id]);
		$far = $this->makeSwitch(['ip' => '10.99.9.9', 'places_id' => $this->makePlace()->id]);

		$arm = new Techs();
		$arm->setAttributes(['model_id' => $near->model_id, 'num' => 'ARM-'.uniqid(),
			'places_id' => $room->id, 'history' => ''], false);
		$this->assertTrue($arm->save(false));

		$targets = $this->makeProvider()->targetsFor($arm);
		$hosts = array_column($targets, 'host');

		$this->assertContains('10.50.2.16', $hosts);        // тот же филиал, глубже по дереву
		$this->assertNotContains('10.99.9.9', $hosts);      // другой филиал
		$this->assertSame($site->id, MacSearchProvider::siteOf($arm));
		$this->assertUnusedTargets($far);
	}

	/** scope=all: опрашиваем всё, независимо от площадки объекта */
	public function testTargetsScopeAll()
	{
		$far = $this->makeSwitch(['ip' => '10.99.9.9', 'places_id' => $this->makePlace()->id]);
		$arm = new Techs();
		$arm->setAttributes(['model_id' => $far->model_id, 'num' => 'ARM-'.uniqid(),
			'places_id' => $this->makePlace()->id, 'history' => ''], false);
		$this->assertTrue($arm->save(false));

		$targets = $this->makeProvider([], ['scope' => 'all'])->targetsFor($arm);
		$this->assertContains('10.99.9.9', array_column($targets, 'host'));
	}

	private function assertUnusedTargets(Techs $tech)
	{
		$this->assertNotEmpty($tech->id);    // страховка: объект создан, просто не попал в цели
	}

	/** Первый IPv4 из многострочного поля адресов */
	public function testFirstIp()
	{
		$this->assertSame('10.50.2.16', MacSearchProvider::firstIp("hostname\n10.50.2.16\n10.0.0.1"));
		$this->assertNull(MacSearchProvider::firstIp('не адрес'));
	}

	// --- опрос и разметка ------------------------------------------------

	/** Запрос к сервису: адрес, цели и ожидание уходят телом POST */
	public function testRequest()
	{
		$provider = $this->makeProvider([$this->response($this->payload([]))], ['wait' => 40]);
		$targets = [['id' => 812, 'host' => '10.50.2.16', 'vendor' => 'Cisco', 'model' => 'X']];

		$provider->search(['001122334455'], $targets);

		$this->assertCount(1, $provider->requests);
		$this->assertSame('http://macsearch.local:8088/api/search', $provider->requests[0]['url']);
		$this->assertSame('001122334455', $provider->requests[0]['body']['mac']);
		$this->assertSame(40, $provider->requests[0]['body']['wait']);
		$this->assertSame($targets, $provider->requests[0]['body']['targets']);
	}

	/** Без целей сервис не дёргаем вовсе */
	public function testNoTargetsNoRequest()
	{
		$provider = $this->makeProvider();
		$this->assertSame([], $provider->search(['001122334455'], []));
		$this->assertSame([], $provider->requests);
	}

	/** Ошибка запроса (нет токена) приходит кодом 4xx с JSON — показываем причину */
	public function testHttpError()
	{
		$provider = $this->makeProvider([$this->response(['status' => 'error',
			'error' => 'нужен токен доступа'], 401)]);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('нужен токен доступа');
		$provider->search(['001122334455'], [['id' => 1, 'host' => '10.50.2.16']]);
	}

	/** Не-JSON в ответе (ответил веб-сервер, а не сервис) — внятная ошибка */
	public function testBadResponse()
	{
		$provider = $this->makeProvider([['<html>403 Forbidden</html>', 403]]);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Некорректный ответ сервиса поиска MAC');
		$provider->search(['001122334455'], [['id' => 1, 'host' => '10.50.2.16']]);
	}

	/** Транзитные порты помечаются по связям портов инвентаризации */
	public function testUplinkAnnotation()
	{
		$access = $this->makeSwitch(['ip' => '10.50.2.16']);
		$core = $this->makeSwitch(['ip' => '10.50.2.1']);

		$uplink = new Ports();
		$uplink->setAttributes(['techs_id' => $access->id, 'name' => 'Gi1/0/48', 'comment' => ''], false);
		$this->assertTrue($uplink->save(false));

		$corePort = new Ports();
		$corePort->setAttributes(['techs_id' => $core->id, 'name' => 'Gi1/0/1',
			'link_ports_id' => $uplink->id, 'comment' => ''], false);
		$this->assertTrue($corePort->save(false));

		$uplink->link_ports_id = $corePort->id;
		$this->assertTrue($uplink->save(false));

		$rows = $this->makeProvider()->annotateUplinks([
			//порт железка назвала иначе, чем инвентаризация - сопоставляем по номеру
			['target' => $access->id, 'port' => 'GigabitEthernet1/0/48', 'vlan' => '1'],
			['target' => $access->id, 'port' => 'Gi1/0/12', 'vlan' => '120'],
		]);

		$this->assertTrue($rows[0]['uplink']);
		$this->assertSame($core->name, $rows[0]['uplink_peer']);
		$this->assertArrayNotHasKey('uplink', $rows[1]);
	}

	/** Сопоставление имён портов: по числовому хвосту, иначе по буквам-цифрам */
	public function testPortKey()
	{
		$this->assertSame(MacSearchProvider::portKey('Gi1/0/12'),
			MacSearchProvider::portKey('GigabitEthernet1/0/12'));
		$this->assertNotSame(MacSearchProvider::portKey('Gi1/0/12'),
			MacSearchProvider::portKey('Gi1/0/13'));
		$this->assertSame(MacSearchProvider::portKey('CPU'), 'cpu');
	}

	// --- рендер ----------------------------------------------------------

	/** Найденный порт: коммутатор ссылкой, VLAN, порт */
	public function testRenderFound()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$provider = $this->makeProvider([$this->response($this->payload([
			['target' => $switch->id, 'host' => '10.50.2.16', 'driver' => 'cisco_ios',
				'mac' => '00:11:22:33:44:55', 'vlan' => '120', 'port' => 'Gi1/0/12'],
		]))]);

		$targets = $provider->targets();      // наполняет карту коммутаторов для ссылок
		$html = $provider->renderResults($provider->search(['001122334455'], $targets));

		$this->assertStringContainsString('Gi1/0/12', $html);
		$this->assertStringContainsString('120', $html);
		//коммутатор показан объектом инвентаризации, а не голым IP
		$this->assertStringContainsString('techs/view', $html);
	}

	/** Ничего не найдено: адрес показан, таблицы нет; недоступные — отдельной строкой */
	public function testRenderNotFound()
	{
		$provider = $this->makeProvider([$this->response($this->payload([], [
			'errors' => [['target' => 5, 'host' => '10.50.2.99', 'error' => 'не удалось подключиться']],
			'targets' => ['requested' => 2, 'answered' => 1, 'failed' => 1],
		]))]);
		$html = $provider->renderResults(
			$provider->search(['001122334455'], [['id' => 1, 'host' => '10.50.2.16']]));

		$this->assertStringContainsString('не найден на портах коммутаторов', $html);
		$this->assertStringContainsString('00:11:22:33:44:55', $html);
		$this->assertStringContainsString('10.50.2.99', $html);
	}

	/**
	 * Неопрошенные коммутаторы — такие же объекты инвентаризации, как найденные:
	 * ссылка на карточку, рядом причина, полный текст ошибки — в подсказке
	 */
	public function testRenderUnreachableAsObjects()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$provider = $this->makeProvider([$this->response($this->payload([], [
			'errors' => [[
				'target' => $switch->id, 'host' => '10.50.2.16',
				'error' => 'нет TCP-соединения (порт закрыт или хост недоступен)',
				'detail' => 'TCP connection to device failed. Device settings: cisco_ios 10.50.2.16:22',
			]],
			'targets' => ['requested' => 1, 'answered' => 0, 'failed' => 1],
		]))]);

		$targets = $provider->targets();      // наполняет карту коммутаторов для ссылок
		$html = $provider->renderResults($provider->search(['001122334455'], $targets));

		$this->assertStringContainsString('не опрошены (1 из 1)', $html);
		//коммутатор показан объектом, а не голым адресом
		$this->assertStringContainsString('techs/view', $html);
		$this->assertStringContainsString($switch->name, $html);
		$this->assertStringContainsString('нет TCP-соединения', $html);
		//подробности netmiko - в подсказке, а не в строке списка
		$this->assertStringContainsString('Device settings', $html);
	}

	/** Опрос ещё идёт: сообщение и самоперезапрос блока */
	public function testRenderPending()
	{
		$provider = $this->makeProvider([$this->response([
			'status' => 'pending', 'job' => '104233-7', 'elapsed' => 20.0,
		])]);
		$results = $provider->search(['001122334455'], [['id' => 1, 'host' => '10.50.2.16']]);

		$this->assertTrue($provider->isPending($results));

		$html = $provider->renderResults($results, '/mac/external?mac=001122334455&attempt=1');
		$this->assertStringContainsString('идёт опрос коммутаторов', $html);
		$this->assertStringContainsString('setTimeout', $html);

		//без URL перезапроса скрипта нет - зовём открыть карточку позже
		$this->assertStringNotContainsString('setTimeout', $provider->renderResults($results));
	}

	/** Панель карточки: собирает цели, опрашивает и рисует результат */
	public function testRenderPanel()
	{
		$place = $this->makePlace();
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);

		$arm = new Techs();
		$arm->setAttributes(['model_id' => $switch->model_id, 'num' => 'ARM-'.uniqid(),
			'places_id' => $place->id, 'mac' => '001122334455', 'history' => ''], false);
		$this->assertTrue($arm->save(false));

		$provider = $this->makeProvider([$this->response($this->payload([
			['target' => $switch->id, 'host' => '10.50.2.16', 'driver' => 'cisco_ios',
				'mac' => '00:11:22:33:44:55', 'vlan' => '120', 'port' => 'Gi1/0/12'],
		]))]);

		$html = $provider->renderPanel(MacSearchProvider::PANEL, $arm);

		//в запрос ушли цели, собранные по площадке объекта
		$this->assertContains('10.50.2.16', array_column($provider->requests[0]['body']['targets'], 'host'));
		$this->assertStringContainsString('Gi1/0/12', $html);
	}

	/**
	 * Пустой результат с уликами сервиса: «не найден» и «не поняли ответ
	 * железки» выглядят одинаково, поэтому диагностику видно в панели —
	 * её можно скопировать в задачу
	 */
	public function testRenderDiagnostics()
	{
		$provider = $this->makeProvider();
		$html = $provider->renderResults([['mac' => '001122334455', 'error' => null,
			'data' => $this->payload([], ['diagnostics' => [[
				'target' => 907, 'host' => '172.16.9.10', 'mode' => 'lookup',
				'commands' => ['show fdb mac_address 00-11-22-33-44-55'],
				'matched' => 0, 'output_chars' => 61,
				'output_head' => 'Command: show fdb mac_address 00-11-22-33-44-55 Fail!',
				'dropped_sample' => ['Fail!'],
				'verdict' => 'ответ не разобран (неизвестный формат либо отказ учётки)',
			]]])]]);

		$this->assertStringContainsString('не найден на портах', $html);
		$this->assertStringContainsString('почему пусто', $html);
		$this->assertStringContainsString('ответ не разобран', $html);
		//сырой ответ железки - главное свидетельство, он должен быть виден целиком
		$this->assertStringContainsString('Fail!', $html);

		//без улик блока нет: обычный «не найден» ничем не обрастает
		$this->assertStringNotContainsString('почему пусто',
			$provider->renderResults([['mac' => '001122334455', 'error' => null,
				'data' => $this->payload([])]]));
	}

	// --- панель коммутатора «Что подключено к портам» ---------------------

	/** Строка таблицы MAC, как её отдаёт сервис в режиме table */
	private function tableRow(int $target, string $mac, string $port, array $extra = []): array
	{
		return array_merge([
			'target' => $target, 'host' => '10.50.2.16', 'driver' => 'cisco_ios',
			'mac' => $mac, 'vlan' => '120', 'port' => $port, 'port_macs' => 1,
		], $extra);
	}

	/** Вторая панель есть только у коммутатора и только кнопкой */
	public function testSwitchPanelOnlyForSwitches()
	{
		$provider = $this->makeProvider();
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);

		$panels = $provider->panels($switch);
		$this->assertArrayHasKey(MacSearchProvider::PANEL_SWITCH, $panels);
		//дорогой опрос не должен уходить сам при открытии карточки
		$this->assertSame('button', $panels[MacSearchProvider::PANEL_SWITCH]['auto']);

		//у ОС портов нет, у коммутатора без адреса опрашивать нечего
		$this->assertArrayNotHasKey(MacSearchProvider::PANEL_SWITCH,
			$provider->panels($this->comp()));
		$this->assertArrayNotHasKey(MacSearchProvider::PANEL_SWITCH,
			$provider->panels($this->makeSwitch(['ip' => ''])));
	}

	/** Ключ кэша коммутатора — сама железка: своего MAC у него может не быть */
	public function testSwitchBindingWithoutMac()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$binding = $this->makeProvider()->binding($switch);

		$this->assertNotNull($binding);
		$this->assertStringContainsString('tech'.$switch->id, $binding);
	}

	/** Опрашивается одна железка и в режиме table (без адреса) */
	public function testSwitchPanelAsksForWholeTable()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$provider = $this->makeProvider([$this->response($this->payload(
			[$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/12')], ['mode' => 'table']))]);

		$provider->renderSwitchPanel($switch);

		$body = $provider->requests[0]['body'];
		$this->assertSame('table', $body['mode']);
		$this->assertArrayNotHasKey('mac', $body);
		//спрашивать площадку ради одной таблицы незачем
		$this->assertCount(1, $body['targets']);
		$this->assertSame('10.50.2.16', $body['targets'][0]['host']);
	}

	/** Раскладка таблицы по портам: устройство, телефон с ПК, транзит */
	public function testSwitchPortsGrouping()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$rows = [
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/1'),
			//телефон и ПК за ним: два адреса на порту, разные VLAN
			$this->tableRow($switch->id, '00:11:22:33:44:60', 'Gi1/0/7',
				['vlan' => '150', 'port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:61', 'Gi1/0/7', ['port_macs' => 2]),
			//за портом сеть
			$this->tableRow($switch->id, '00:11:22:33:44:70', 'Gi1/0/48', ['port_macs' => 37]),
		];

		$ports = $this->makeProvider()->switchPorts($rows);

		$this->assertSame(['Gi1/0/1', 'Gi1/0/7', 'Gi1/0/48'], array_column($ports, 'port'));
		$this->assertFalse($ports[0]['transit']);
		//два адреса - ещё не транзит: это штатное рабочее место с телефоном
		$this->assertFalse($ports[1]['transit']);
		$this->assertCount(2, $ports[1]['macs']);
		$this->assertSame(['150', '120'], $ports[1]['vlans']);
		$this->assertTrue($ports[2]['transit']);
		$this->assertSame(37, $ports[2]['count']);
	}

	/** Связь портов перебивает счёт адресов: транзит даже с одним адресом */
	public function testSwitchPortsTrustPortLinks()
	{
		$access = $this->makeSwitch(['ip' => '10.50.2.16']);
		$core = $this->makeSwitch(['ip' => '10.50.2.1']);

		$uplink = new Ports();
		$uplink->setAttributes(['techs_id' => $access->id, 'name' => 'Gi1/0/48', 'comment' => ''], false);
		$this->assertTrue($uplink->save(false));
		$corePort = new Ports();
		$corePort->setAttributes(['techs_id' => $core->id, 'name' => 'Gi1/0/1',
			'link_ports_id' => $uplink->id, 'comment' => ''], false);
		$this->assertTrue($corePort->save(false));
		$uplink->link_ports_id = $corePort->id;
		$this->assertTrue($uplink->save(false));

		$provider = $this->makeProvider();
		$rows = $provider->annotateUplinks([
			$this->tableRow($access->id, '00:11:22:33:44:55', 'Gi1/0/48'),
		]);
		$ports = $provider->switchPorts($rows);

		$this->assertTrue($ports[0]['uplink']);
		$this->assertTrue($ports[0]['transit']);
		$this->assertSame($core->name, $ports[0]['uplink_peer']);
	}

	/** Адреса таблицы -> объекты инвентаризации (железо и ОС, одним запросом) */
	public function testResolveMacs()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);

		$arm = new Techs();
		$arm->setAttributes(['model_id' => $switch->model_id, 'num' => 'ARM-'.uniqid(),
			'mac' => '001122334455', 'history' => ''], false);
		$this->assertTrue($arm->save(false));

		$comp = new Comps();
		$comp->setAttributes(['name' => 'OS-'.uniqid(), 'mac' => "0011223344aa\n0011223344bb"], false);
		$this->assertTrue($comp->save(false));

		$found = $this->makeProvider()->resolveMacs([
			'00:11:22:33:44:55', '00-11-22-33-44-bb', '00:00:00:00:00:99', 'мусор',
		]);

		$this->assertSame([$arm->id], array_map(fn($item) => $item->id, $found['001122334455']));
		//адрес записан на ОС - объект всё равно находится
		$this->assertSame([$comp->id], array_map(fn($item) => $item->id, $found['0011223344bb']));
		$this->assertArrayNotHasKey('000000000099', $found);
	}

	/** Рендер панели: порт, объект за ним ссылкой и пометка транзита */
	public function testRenderSwitchPanel()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);

		$arm = new Techs();
		//короткий номер: длинное имя виджет объекта подрезает при выводе
		$arm->setAttributes(['model_id' => $switch->model_id, 'num' => 'ARM-PORT12',
			'mac' => '001122334455', 'history' => ''], false);
		$this->assertTrue($arm->save(false));

		$provider = $this->makeProvider([$this->response($this->payload([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/12'),
			$this->tableRow($switch->id, '00:aa:bb:cc:dd:ee', 'Gi1/0/48', ['port_macs' => 37]),
		], ['mode' => 'table']))]);

		$html = $provider->renderSwitchPanel($switch);

		$this->assertStringContainsString('Gi1/0/12', $html);
		//объект за портом - ссылкой на карточку, а не адресом
		$this->assertStringContainsString($arm->name, $html);
		$this->assertStringContainsString('адресов: 37', $html);
	}

	/** Неопрошенный коммутатор: причина видна прямо в панели */
	public function testRenderSwitchPanelUnreachable()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$provider = $this->makeProvider([$this->response($this->payload([], [
			'mode' => 'table',
			'targets' => ['requested' => 1, 'answered' => 0, 'failed' => 1],
			'errors' => [['target' => $switch->id, 'host' => '10.50.2.16',
				'error' => 'нет TCP-соединения (порт закрыт или хост недоступен)',
				'detail' => 'TCP connection to device failed']],
		]))]);

		$html = $provider->renderSwitchPanel($switch);
		$this->assertStringContainsString('нет TCP-соединения', $html);
		$this->assertStringContainsString('TCP connection to device failed', $html);
	}
}
