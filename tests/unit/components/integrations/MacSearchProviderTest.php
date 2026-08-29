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

			protected function httpPost(string $url, string $body, ?int $timeout = null): array
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
	 * но устройство могли перевезти — тогда «по всем площадкам». Разные области
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

	/**
	 * Стек (plans/network-map.md, 3.0.4): члены с общим management-IP на одной
	 * площадке - одна цель опроса (представитель - первый по id), а результат
	 * раскладывается по членам по их объявленным портам. Одинаковый IP в
	 * другом филиале - другой стек.
	 */
	public function testStackIsOneTargetAndRowsSpreadByPorts()
	{
		$place = $this->makePlace();
		$first = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);
		$second = $this->makeSwitch(['ip' => "10.50.2.16
10.50.2.17", 'places_id' => $place->id]);
		$elsewhere = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $this->makePlace()->id]);
		$first->ports_override = "Gi1/0/1
Gi1/0/2";
		$second->ports_override = "Gi2/0/1
Gi2/0/2";
		$this->assertTrue($first->save(false) && $second->save(false));
		$first->refresh();
		$second->refresh();

		$provider = $this->makeProvider();
		$targets = $provider->targets([$place->id, $elsewhere->places_id]);
		$this->assertSame([$first->id, $elsewhere->id], array_column($targets, 'id'));
		//оба члена известны как коммутаторы - ссылки в результате настоящие
		$this->assertArrayHasKey($second->id, $provider->switches());

		$rows = $provider->attributeStack([
			$this->tableRow($first->id, '00:11:22:33:44:01', 'Gi1/0/2'),
			$this->tableRow($first->id, '00:11:22:33:44:02', 'Gi2/0/1'),
			//порта нет ни у кого: остаётся на представителе с пометкой
			$this->tableRow($first->id, '00:11:22:33:44:03', 'Gi3/0/7'),
			$this->tableRow($elsewhere->id, '00:11:22:33:44:04', 'Gi2/0/1'),
		]);
		$this->assertSame([$first->id, $second->id, $first->id, $elsewhere->id],
			array_column($rows, 'target'));
		$this->assertTrue($rows[2]['stack_unassigned']);
		$this->assertArrayNotHasKey('stack_unassigned', $rows[1]);

		//члены с одинаковыми именами (оба по модельному шаблону Gi1/0/x)
		//неразличимы - порт остаётся на представителе, без догадок
		$second->ports_override = "Gi1/0/1
Gi1/0/2";
		$this->assertTrue($second->save(false));
		$second->refresh();
		$this->assertNull(MacSearchProvider::portOwner('Gi1/0/1', [$first, $second]));
	}

	/** Карточка члена стека показывает только его порты */
	public function testStackMemberPanelShowsOwnPorts()
	{
		$place = $this->makePlace();
		$first = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);
		$second = $this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);
		$first->ports_override = "Gi1/0/1
Gi1/0/2";
		$second->ports_override = "Gi2/0/1
Gi2/0/2";
		$this->assertTrue($first->save(false) && $second->save(false));
		$first->refresh();
		$second->refresh();

		$payload = $this->payload([
			$this->tableRow($second->id, '00:11:22:33:44:01', 'Gi1/0/2'),
			$this->tableRow($second->id, '00:11:22:33:44:02', 'Gi2/0/1'),
		], ['mode' => 'table', 'ports' => [
			['name' => 'Gi1/0/1', 'type' => 6, 'description' => 'first-only'],
			['name' => 'Gi2/0/1', 'type' => 6, 'description' => 'second-only'],
		]]);

		$html = $this->makeProvider([$this->response($payload)])->renderSwitchPanel($second);
		$this->assertStringContainsString('стек', $html);
		$this->assertStringContainsString('second-only', $html);
		$this->assertStringNotContainsString('first-only', $html);
		//порты соседа не предлагаются как «взять имена»
		$this->assertStringNotContainsString('Gi1\/0\/1', $html);
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
			//порт коммутатор назвал иначе, чем инвентаризация - сопоставляем по номеру
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
	 * коммутатора» выглядят одинаково, поэтому диагностику видно в панели —
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
		//сырой ответ коммутатора - главное свидетельство, он должен быть виден целиком
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
		//сама панель нигде не рисуется: её зовёт поимённо блок «Сетевые порты»
		//и подменяет ею свою таблицу (таблица портов в карточке одна)
		$this->assertFalse($panels[MacSearchProvider::PANEL_SWITCH]['auto']);
		$this->assertSame('Опросить порты', $panels[MacSearchProvider::PANEL_SWITCH]['button']);

		//у ОС портов нет, у коммутатора без адреса опрашивать нечего
		$this->assertArrayNotHasKey(MacSearchProvider::PANEL_SWITCH,
			$provider->panels($this->comp()));
		$this->assertArrayNotHasKey(MacSearchProvider::PANEL_SWITCH,
			$provider->panels($this->makeSwitch(['ip' => ''])));
	}

	/** Ключ кэша коммутатора — сам коммутатор: своего MAC у него может не быть */
	public function testSwitchBindingWithoutMac()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$binding = $this->makeProvider()->binding($switch);

		$this->assertNotNull($binding);
		$this->assertStringContainsString('tech'.$switch->id, $binding);
	}

	/** Прогрев (ping-sweep) уходит в запрос только по явной просьбе */
	public function testSiteNeighborsSendsSweepNetworks()
	{
		$place = $this->makePlace();
		$this->makeSwitch(['ip' => '10.50.2.16', 'places_id' => $place->id]);

		$provider = $this->makeProvider([$this->response($this->payload([], ['mode' => 'table']))]);
		$provider->siteNeighbors($place->id, ['10.50.2.0/24']);
		$this->assertSame(['10.50.2.0/24'], $provider->requests[0]['body']['sweep']);

		//без просьбы ключа sweep в теле нет вовсе: активное вмешательство
		//не должно включаться само
		$provider = $this->makeProvider([$this->response($this->payload([], ['mode' => 'table']))]);
		$provider->siteNeighbors($place->id);
		$this->assertArrayNotHasKey('sweep', $provider->requests[0]['body']);
	}

	/** Подсети прогрева - из IPAM: сети площадки через VLAN и сетевой домен */
	public function testSiteNetworksFromIpam()
	{
		$place = $this->makePlace();
		$domain = new \app\models\NetDomains();
		$domain->setAttributes(['name' => 'dom-'.uniqid(), 'places_id' => $place->id], false);
		$this->assertTrue($domain->save(false));
		$vlan = new \app\models\NetVlans();
		$vlan->setAttributes(['name' => 'vl-sweep', 'vlan' => 3120, 'domain_id' => $domain->id], false);
		$this->assertTrue($vlan->save(false));

		$network = new \app\models\Networks();
		$network->setAttributes(['text_addr' => '10.50.2.0/24', 'vlan_id' => $vlan->id], false);
		$this->assertTrue($network->save(false));
		//архивная сеть в прогрев не идёт
		$archived = new \app\models\Networks();
		$archived->setAttributes(['text_addr' => '10.50.3.0/24', 'vlan_id' => $vlan->id,
			'archived' => 1], false);
		$this->assertTrue($archived->save(false));

		$this->assertSame(['10.50.2.0/24'], MacSearchProvider::siteNetworks($place->id));
	}

	/** Опрашивается один коммутатор и в режиме table (без адреса) */
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

	/**
	 * Объявленные порты задают и порядок, и состав: инженеру нужен не только
	 * занятый порт, но и свободный — «куда воткнуть»
	 */
	public function testSwitchPortsFollowDeclaredLayout()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->model->ports = "Gi1/0/1\nGi1/0/2 в патч-панель\nGi1/0/3\nGi1/0/4";
		$this->assertTrue($switch->model->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/3'),
			$this->tableRow($switch->id, '00:11:22:33:44:56', 'Gi1/0/1'),
			//порта Po1 в объявлении нет - это находка, а не мусор
			$this->tableRow($switch->id, '00:11:22:33:44:57', 'Po1'),
		], $switch);

		//объявленный порядок, а не сортировка по номеру и не порядок ответа
		$this->assertSame(['Gi1/0/1', 'Gi1/0/2', 'Gi1/0/3', 'Gi1/0/4', 'Po1'],
			array_column($ports, 'port'));
		//свободные розетки видно: без объявления их вообще не было бы в выдаче
		$this->assertSame([false, true, false, true, false],
			array_map(fn($port) => !count($port['macs']), $ports));
		//комментарий объявления доезжает до панели
		$this->assertSame('в патч-панель', $ports[1]['comment']);
		$this->assertTrue($ports[0]['declared']);
		$this->assertFalse($ports[4]['declared']);
	}

	/**
	 * Сопоставление объявленного с реальным - ТОЛЬКО по точному имени.
	 *
	 * Конвенция владельца: имена в модели обязаны совпадать с именами
	 * железки. Раньше «1» тихо матчился с «Gi1/0/1» по номеру, и кривое
	 * объявление маскировалось наполовину; теперь несопоставленные порты
	 * вылезают отдельными строками - ошибка видна целиком и лечится кнопкой
	 * «взять имена с коммутатора».
	 */
	public function testSwitchPortsMatchDeclaredOnlyExactly()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->model->ports = "1\n2";
		$this->assertTrue($switch->model->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/1'),
			$this->tableRow($switch->id, '00:11:22:33:44:56', 'Gi1/0/2'),
		], $switch);

		//объявленные пустые, реальные - хвостом: рассинхрон виден целиком
		$this->assertSame(['1', '2', 'Gi1/0/1', 'Gi1/0/2'], array_column($ports, 'port'));
		$this->assertSame([0, 0, 1, 1], array_map(fn($port) => count($port['macs']), $ports));
	}

	/** Без объявления - прежнее поведение: только найденные порты, по номеру */
	public function testSwitchPortsWithoutLayout()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/10'),
			$this->tableRow($switch->id, '00:11:22:33:44:56', 'Gi1/0/2'),
		], $switch);

		$this->assertSame(['Gi1/0/2', 'Gi1/0/10'], array_column($ports, 'port'));
	}

	/**
	 * Порт коммутатора, соединённый с портом другого устройства.
	 *
	 * В `ports` есть только link_ports_id: связь всегда идёт порт-в-порт, а
	 * «оборудование на той стороне» вычисляется через встречный порт.
	 */
	private function linkPort(Techs $switch, string $name, ?Techs $peer = null): Ports
	{
		$port = new Ports();
		$port->setAttributes(['techs_id' => $switch->id, 'name' => $name, 'comment' => ''], false);
		$this->assertTrue($port->save(false));
		if (!is_object($peer)) return $port;

		$peerPort = new Ports();
		$peerPort->setAttributes(['techs_id' => $peer->id, 'name' => 'eth0',
			'link_ports_id' => $port->id, 'comment' => ''], false);
		$this->assertTrue($peerPort->save(false));

		$port->link_ports_id = $peerPort->id;
		$this->assertTrue($port->save(false));
		return $port;
	}

	/** Устройство с адресом (то, что должно найтись за портом) */
	private function makeDevice(Techs $switch, string $mac, string $num, ?string $ports = null): Techs
	{
		$modelId = $switch->model_id;
		//своя модель с объявленными портами: у телефона их два, у ПК один
		if (!is_null($ports)) {
			$model = new TechModels();
			$model->setAttributes(['name' => 'Модель '.uniqid(),
				'manufacturers_id' => $switch->model->manufacturers_id, 'ports' => $ports,
				'comment' => ''], false);
			$this->assertTrue($model->save(false));
			$modelId = $model->id;
		}
		$tech = new Techs();
		$tech->setAttributes(['model_id' => $modelId, 'num' => $num,
			'mac' => $mac, 'history' => ''], false);
		$this->assertTrue($tech->save(false));
		$tech->refresh();
		return $tech;
	}

	/**
	 * Вердикты диффа: что записано против того, что видно на порту.
	 *
	 * Главное здесь - отсутствие вердикта «пропало»: порт без адресов значит
	 * и «убрали», и «выключено», и «молчит дольше старения записи», а стирать
	 * правильную запись по такому основанию нельзя.
	 */
	public function testPortVerdicts()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = "1\n2\n3\n4\n5\n6";
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$server = $this->makeDevice($switch, '001122334455', 'SRV-OK');
		$other = $this->makeDevice($switch, '001122334466', 'SRV-OTHER');

		$this->linkPort($switch, '1', $server);      // найдём его же
		$this->linkPort($switch, '2', $server);      // найдём другого
		$this->linkPort($switch, '3', $server);      // адреса чужие
		$this->linkPort($switch, '4', $server);      // тишина
		//порт 5 не записан, но на нём найдётся известное железо
		//порт 6 не записан, и адрес на нём неизвестен

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:55', '1'),
			$this->tableRow($switch->id, '00:11:22:33:44:66', '2'),
			$this->tableRow($switch->id, '00:aa:bb:cc:dd:01', '3'),
			$this->tableRow($switch->id, '00:11:22:33:44:66', '5'),
			$this->tableRow($switch->id, '00:aa:bb:cc:dd:02', '6'),
		], $switch);

		$verdicts = array_combine(array_column($ports, 'port'), array_column($ports, 'verdict'));
		$this->assertSame([
			'1' => 'ok',        // нашли то, что записано
			'2' => 'replaced',  // на порту другое известное железо
			'3' => 'foreign',   // адреса есть, но чьи - неизвестно
			'4' => 'quiet',     // адресов нет вовсе: не повод стирать запись
			'5' => 'added',     // записано пусто, найдено известное
			'6' => 'seen',      // адрес есть, объекта с ним нет
		], $verdicts);

		//у «заменить» видно, на что менять
		$replaced = $ports[1];
		$this->assertSame([$other->id], array_map(fn($tech) => $tech->id, $replaced['found']));
		$this->assertSame($server->id, $replaced['linked']->id);
	}

	/** Адрес записан на ОС - за портом стоит её АРМ, а не сама ОС */
	public function testFoundDeviceForOsAddress()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$arm = $this->makeDevice($switch, '', 'ARM-OS');

		$comp = new Comps();
		$comp->setAttributes(['name' => 'OS-'.uniqid(), 'mac' => '0011223344cc',
			'arm_id' => $arm->id], false);
		$this->assertTrue($comp->save(false));

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:cc', 'Gi1/0/5'),
		], $switch);

		$this->assertSame('added', $ports[0]['verdict']);
		$this->assertSame([$arm->id], array_map(fn($tech) => $tech->id, $ports[0]['found']));
	}

	/** Запись паспорта порта, как её отдаёт сервис */
	private function passportPort(string $name, array $extra = []): array
	{
		return array_merge([
			'name' => $name, 'description' => '', 'admin' => 'up', 'oper' => 'up',
			'speed' => 1000, 'aggregate' => '', 'vlans' => [],
		], $extra);
	}

	/**
	 * Паспорт портов: описание с коммутатора, настроенные VLAN и «выключен»
	 * вместо «свободен»
	 */
	public function testPassportEnrichesPorts()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = "Gi1/0/1\nGi1/0/2";
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([], $switch, [
			$this->passportPort('Gi1/0/1', ['description' => 'to core',
				'vlans' => [['vlan' => 120, 'untagged' => true],
					['vlan' => 150, 'untagged' => false]]]),
			$this->passportPort('Gi1/0/2', ['admin' => 'down', 'oper' => 'down']),
		]);

		$this->assertSame('to core', $ports[0]['description']);
		//VLAN настроенные, а не «где замечен трафик»
		$this->assertTrue($ports[0]['vlans_configured']);
		$this->assertSame(['120', '150'], array_column($ports[0]['vlans'], 'vlan'));
		$this->assertTrue($ports[0]['vlans'][0]['untagged']);
		//выключенный порт - не свободный: воткнуть в него нельзя
		$this->assertSame('disabled', $ports[1]['verdict']);
	}

	/**
	 * Инвентаризация и коммутатор зовут одну розетку по-разному.
	 *
	 * Показываем объявленное имя (оно с корпуса), но паспорт и соседей ищем
	 * по имени коммутатора - иначе описание порта и VLAN просто не доедут, а
	 * «взять имена портов» будет нечего брать.
	 */
	public function testRealNameKeptAlongsideDeclared()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = "Gi1/0/1\nGi1/0/2";
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([], $switch, [
			$this->passportPort('Gi1/0/1', ['description' => 'to core']),
			$this->passportPort('Gi1/0/2'),
		]);

		//объявление по конвенции совпадает с именами железки - паспорт доехал
		$this->assertSame('Gi1/0/1', $ports[0]['port']);
		$this->assertSame('Gi1/0/1', $ports[0]['real']);
		$this->assertSame('to core', $ports[0]['description']);
	}

	/**
	 * Сосед, повторяющий уже показанное устройство, не рисуется отдельной
	 * строкой: телефон честно виден в FDB, LLDP и CDP, но человеку это один
	 * узел. CDP телефонов кладёт MAC в Port ID - опознаём и по нему.
	 */
	public function testNeighborDuplicatingVisibleDeviceIsHidden()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->model->ports = "Gi1/0/10";
		$this->assertTrue($switch->model->save(false));
		$switch->refresh();

		$phone = new Techs();
		$phone->setAttributes(['model_id' => $switch->model_id, 'num' => 'TEL-10',
			'mac' => '00da55b88a3b', 'history' => ''], false);
		$this->assertTrue($phone->save(false));

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:da:55:b8:8a:3b', 'Gi1/0/10'),
		], $switch, [], [
			//CDP: имя без адреса, MAC - в Port ID
			['target' => $switch->id, 'port' => 'Gi1/0/10', 'remote_mac' => '',
				'remote_name' => 'Cisco IP Phone SPA504G',
				'remote_port' => '00da.55b8.8a3b', 'protocol' => 'cdp'],
			//LLDP: MAC зашит в имя
			['target' => $switch->id, 'port' => 'Gi1/0/10', 'remote_mac' => '',
				'remote_name' => 'SIP00DA55B88A3B', 'remote_port' => 'Port 1',
				'protocol' => 'lldp'],
		]);

		//устройство найдено по FDB, обе соседские строки - его же дубли
		$this->assertSame([$phone->id], array_map(fn($device) => $device->id,
			$ports[0]['found']));
		$this->assertSame([], $ports[0]['neighbors']);
		//Port ID-МАК именем порта не становится, а LLDP-имя розетки - да
		$this->assertNotSame('00da.55b8.8a3b', $ports[0]['neighbor_port'] ?? '');
	}

	/**
	 * Рассинхрон объявления с железкой не «склеивается» приблизительно:
	 * паспорт чужих имён остаётся при реальных портах, объявленные - пустые.
	 */
	public function testMismatchedDeclarationIsNotGlued()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = "Ge0/1\nGe0/2";
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([], $switch, [
			$this->passportPort('Gi1/0/1', ['description' => 'to core']),
			$this->passportPort('Gi1/0/2'),
		]);

		$byName = [];
		foreach ($ports as $port) $byName[$port['port']] = $port;
		//объявленный порт без паспорта: имён коммутатор про него не говорил
		$this->assertSame('', (string)$byName['Ge0/1']['real']);
		//реальные порты вылезли отдельно, паспорт при них
		$this->assertSame('to core', $byName['Gi1/0/1']['description']);
	}

	/**
	 * Интерфейсы без розетки в таблице портов не показываются.
	 *
	 * Сам агрегат, VLAN-интерфейс, loopback коммутатор перечисляет наравне с
	 * портами, но воткнуть в них нечего, а на 48-портовом коммутаторе их ещё с
	 * десяток. Отличает их тип интерфейса (ifType 6 - ethernetCsmacd), без
	 * него - имя. Объявленное в инвентаризации имя показывается всегда.
	 */
	public function testVirtualInterfacesLeftOut()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = 'Gi1/0/1';
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$ports = $this->makeProvider()->switchPorts([], $switch, [
			$this->passportPort('Gi1/0/1', ['type' => 6]),
			$this->passportPort('Po1', ['type' => 161]),
			$this->passportPort('Vlan120', ['type' => 53]),
		]);
		$this->assertSame(['Gi1/0/1'], array_column($ports, 'port'));

		//сервис постарше типа не присылает - тогда судим по имени
		$older = $this->makeProvider()->switchPorts([], $switch, [
			$this->passportPort('Gi1/0/1'),
			$this->passportPort('Po1'),
			$this->passportPort('Vlan1'),
			$this->passportPort('Gi1/0/2'),
		]);
		$this->assertSame(['Gi1/0/1', 'Gi1/0/2'], array_column($older, 'port'));

		//коммутатор без объявленных портов: список берётся из паспорта - и там
		//те же правила, иначе Po1 и Vlan1 возвращаются с чёрного хода
		$bare = $this->makeSwitch(['ip' => '10.50.2.17']);
		$bare->model->ports = '';
		$this->assertTrue($bare->model->save(false));
		$bare->refresh();
		$fromPassport = $this->makeProvider()->switchPorts([], $bare, [
			$this->passportPort('Gi1/0/1', ['type' => 6]),
			$this->passportPort('Po1', ['type' => 161]),
			$this->passportPort('mgmt0', ['type' => 6]),
			$this->passportPort('Vlan1', ['type' => 53]),
		]);
		$this->assertSame(['Gi1/0/1', 'mgmt0'], array_column($fromPassport, 'port'));
	}

	/**
	 * Адреса с агрегата ложатся на его порты.
	 *
	 * Таблицу MAC коммутатор ведёт на Po1, а не на Gi1/0/47: без раскладки по
	 * членам обе розетки группы выглядели бы пустыми, а строка Po1 - занятой,
	 * хотя воткнуть в неё нечего. Группа при этом - ярлык, а не вердикт:
	 * каждый член сравнивается с записанным как обычный порт.
	 */
	public function testAggregateMacsSpreadToMembers()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = "Gi1/0/47\nGi1/0/48";
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$server = $this->makeDevice($switch, '001122334455', 'SRV');

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Po1'),
		], $switch, [
			$this->passportPort('Gi1/0/47', ['type' => 6, 'aggregate' => 'Po1']),
			$this->passportPort('Gi1/0/48', ['type' => 6, 'aggregate' => 'Po1']),
			$this->passportPort('Po1', ['type' => 161]),
		]);

		$this->assertSame(['Gi1/0/47', 'Gi1/0/48'], array_column($ports, 'port'));
		foreach ($ports as $port) {
			$this->assertSame('Po1', $port['aggregate']);
			$this->assertSame([$server->id], array_map(fn($device) => $device->id, $port['found']));
			//записано пусто, найдено известное - обычное предложение привязать
			$this->assertSame('added', $port['verdict']);
		}
	}

	/**
	 * За портом виден сам коммутатор (служебный порт CPU у D-Link, петля):
	 * предлагать связь с самим собой нельзя - это предупреждение, а не находка
	 */
	public function testOwnAddressIsWarningNotOffer()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'mac' => '00aabbccdd01']);
		$switch->ports_override = "Gi1/0/1\nCPU";
		$this->assertTrue($switch->save(false));
		$switch->refresh();
		$server = $this->makeDevice($switch, '001122334455', 'SRV');

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:aa:bb:cc:dd:01', 'CPU'),
			//на обычном порту свой адрес рядом с чужим: чужой - находка, свой - нет
			$this->tableRow($switch->id, '00:aa:bb:cc:dd:01', 'Gi1/0/1', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/1', ['port_macs' => 2]),
		], $switch);
		$byName = array_column($ports, null, 'port');

		$this->assertSame('self', $byName['CPU']['verdict']);
		$this->assertSame([], $byName['CPU']['proposals']);
		$this->assertTrue($byName['Gi1/0/1']['self']);
		$this->assertSame([$server->id], array_map(fn($d) => $d->id, $byName['Gi1/0/1']['found']));
		$this->assertSame('added', $byName['Gi1/0/1']['verdict']);
	}

	/** Опознанный сосед по LLDP становится предложением связи */
	public function testNeighborBecomesLinkOffer()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = 'Gi1/0/48';
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$core = $this->makeSwitch(['ip' => '10.50.2.1', 'mac' => '00aabbccddee']);

		$ports = $this->makeProvider()->switchPorts([
			//за портом сеть: без соседа это был бы просто «транзит»
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/48', ['port_macs' => 37]),
		], $switch, [], [[
			'target' => $switch->id, 'port' => 'Gi1/0/48', 'protocol' => 'lldp',
			'remote_mac' => '00:aa:bb:cc:dd:ee', 'remote_port' => 'Gi1/0/1',
			'remote_name' => 'sw-core',
		]]);

		//сосед - факт с коммутатора, он важнее счёта адресов
		$this->assertSame('added', $ports[0]['verdict']);
		$this->assertSame([$core->id], array_map(fn($tech) => $tech->id, $ports[0]['found']));
		//порт на той стороне сосед назвал сам - гадать не нужно
		$this->assertCount(1, $ports[0]['proposals']);
		$this->assertSame([['id' => null, 'name' => 'Gi1/0/1']], $ports[0]['proposals'][0]['peers']);
		$this->assertNull($ports[0]['proposals'][0]['chain']);
	}

	/**
	 * Телефон с ПК за ним: у одного найденного два порта (может быть мостом),
	 * у другого один - предлагаем цепочку «порт → телефон ; телефон → ПК».
	 * Когда мостом может быть любое, схему не выдумываем - список кандидатов.
	 */
	public function testPhoneWithPcBecomesChain()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$switch->ports_override = 'Gi1/0/7';
		$this->assertTrue($switch->save(false));
		$switch->refresh();

		$phone = $this->makeDevice($switch, '001122334401', 'TEL', "Internet
PC");
		$pc = $this->makeDevice($switch, '001122334402', 'PC', 'eth');

		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:01', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:02', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);

		$this->assertCount(1, $ports[0]['proposals'], 'одна цепочка, а не два кандидата');
		$proposal = $ports[0]['proposals'][0];
		$this->assertSame($phone->id, $proposal['device']->id);
		//оба порта моста остаются в предложении: какой смотрит в коммутатор -
		//переключатель, Internet лишь подсказан первым
		$this->assertSame(['Internet', 'PC'], array_column($proposal['peers'], 'name'));
		$this->assertSame('PC', $proposal['chain']['via']['name']);
		$this->assertSame($pc->id, $proposal['chain']['leaf']->id);
		$this->assertSame('eth', $proposal['chain']['leaf_peers'][0]['name']);

		//имена портов к коммутатору настраиваются: у этого вендора это «sw»
		$custom = $this->makeDevice($switch, '001122334403', 'TEL2', "pc
sw");
		$ports = $this->makeProvider([], ['bridgeToSwitchPorts' => ['sw']])->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:03', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:02', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);
		$this->assertSame(['sw', 'pc'], array_column($ports[0]['proposals'][0]['peers'], 'name'));

		//лист без объявленных портов - тоже лист: привязывается без порта
		$bare = $this->makeDevice($switch, '001122334404', 'PC-BARE', '');
		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:01', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:04', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);
		$this->assertCount(1, $ports[0]['proposals']);
		$this->assertSame($bare->id, $ports[0]['proposals'][0]['chain']['leaf']->id);
		$this->assertSame([], $ports[0]['proposals'][0]['chain']['leaf_peers']);

		//записанное сошлось, но рядом нашлось второе - цепочка поверх записи:
		//телефон уже на порту, за ним обнаружился ПК
		$recorded = Ports::forTech($switch, 'Gi1/0/7');
		$recorded->link_techs_id = $phone->id;
		$recorded->link_ports_id = 'create:Internet';
		$this->assertTrue($recorded->save(), implode('; ', $recorded->firstErrors));
		$switch->refresh();
		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:01', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:02', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);
		$this->assertSame('ok', $ports[0]['verdict'], 'записанное на месте');
		$this->assertCount(1, $ports[0]['proposals']);
		$proposal = $ports[0]['proposals'][0];
		$this->assertSame($phone->id, $proposal['device']->id);
		//порт моста к нам - тот, которым он уже записан, а не подсказка по именам
		$this->assertSame('Internet', $proposal['peers'][0]['name']);
		$this->assertNotEmpty($proposal['peers'][0]['id']);
		$this->assertSame($pc->id, $proposal['chain']['leaf']->id);

		//наоборот: записан ПК, а телефон воткнули между ним и коммутатором -
		//порт ПК, которым он смотрит на нас, остаётся портом листа
		$recorded->link_techs_id = $pc->id;
		$recorded->link_ports_id = 'create:eth';
		$this->assertTrue($recorded->save(), implode('; ', $recorded->firstErrors));
		$switch->refresh();
		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:01', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:02', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);
		$this->assertSame('ok', $ports[0]['verdict']);
		$this->assertCount(1, $ports[0]['proposals']);
		$this->assertSame($phone->id, $ports[0]['proposals'][0]['device']->id);
		$this->assertSame('eth', $ports[0]['proposals'][0]['chain']['leaf_peers'][0]['name']);
		$recorded->dropLink();
		$switch->refresh();

		//два устройства с двумя портами: кто мост - неизвестно, значит список
		$pc->model->ports = "eth0
eth1";
		$this->assertTrue($pc->model->save(false));
		$ports = $this->makeProvider()->switchPorts([
			$this->tableRow($switch->id, '00:11:22:33:44:01', 'Gi1/0/7', ['port_macs' => 2]),
			$this->tableRow($switch->id, '00:11:22:33:44:02', 'Gi1/0/7', ['port_macs' => 2]),
		], $switch);
		$this->assertCount(2, $ports[0]['proposals']);
		$this->assertNull($ports[0]['proposals'][0]['chain']);
	}

	/** Сосед опознаётся и по имени, когда адрес не записан */
	public function testNeighborByName()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);
		$core = $this->makeSwitch(['ip' => '10.50.2.1', 'hostname' => 'sw-core-1']);

		$ports = $this->makeProvider()->switchPorts([], $switch, [], [[
			'target' => $switch->id, 'port' => 'Gi1/0/48', 'protocol' => 'cdp',
			'remote_mac' => '', 'remote_port' => 'Gi0/1',
			//CDP печатает FQDN - домен отбрасываем
			'remote_name' => 'sw-core-1.local',
		]]);

		$this->assertSame([$core->id], array_map(fn($tech) => $tech->id, $ports[0]['found']));
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
		//порт с сетью за ним помечен транзитом, а не списком из 37 предложений
		$this->assertStringContainsString('транзит', $html);
		//сырые данные с коммутатора - свёрнутым блоком под таблицей
		$this->assertStringContainsString('показать данные с коммутатора', $html);
		//SNMP-данных нет совсем - панель говорит про community, а не молчит
		$this->assertStringContainsString('проверьте, задан ли community', $html);
	}

	/** Визитка в панели опроса: данные устройства показаны как есть, без сверки */
	public function testRenderSwitchPanelShowsIdentity()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'sn' => 'REAL-111']);

		$payload = $this->payload([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/12'),
		], ['mode' => 'table']);
		$payload['identity'] = [['target' => $switch->id, 'host' => '10.50.2.16',
			'sysname' => 'sw-somewhere', 'base_mac' => '00:11:22:33:44:99',
			'sysdescr' => 'Fake OS 1.0',
			'units' => [
				['class' => 'chassis', 'name' => 'Unit 1', 'serial' => 'ALIEN-222',
					'model' => 'FS-2400', 'sw' => '1.0.42'],
				['class' => 'powerSupply', 'name' => 'PSU 1', 'serial' => 'PSU-1'],
			]]];
		$provider = $this->makeProvider([$this->response($payload)]);

		$html = $provider->renderSwitchPanel($switch);

		$this->assertStringContainsString('коммутатор о себе', $html);
		$this->assertStringContainsString('sw-somewhere', $html);
		$this->assertStringContainsString('00:11:22:33:44:99', $html);
		//серийники по юнитам, с моделью и прошивкой
		$this->assertStringContainsString('ALIEN-222', $html);
		$this->assertStringContainsString('Unit 1', $html);
		$this->assertStringContainsString('FS-2400, ПО 1.0.42', $html);
		$this->assertStringContainsString('PSU-1', $html);
		$this->assertStringContainsString('Fake OS 1.0', $html);
		//визитка есть - подсказка про community не нужна
		$this->assertStringNotContainsString('community', $html);
	}

	/** Диагностика возможностей в панели: команды, права и «LLDP включён» видны */
	public function testRenderSwitchPanelShowsCapabilities()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16']);

		$payload = $this->payload([
			$this->tableRow($switch->id, '00:11:22:33:44:55', 'Gi1/0/12'),
		], ['mode' => 'table']);
		$payload['capabilities'] = [[
			'target' => $switch->id, 'host' => '10.50.2.16', 'driver' => 'cisco_ios',
			'cli' => ['available' => true, 'capabilities' => [
				'fdb' => ['status' => 'ok', 'rows' => 120, 'commands' => ['show mac address-table']],
				'neighbors' => ['status' => 'ok', 'rows' => 2, 'lldp_enabled' => true,
					'commands' => ['show lldp neighbors detail', 'show cdp neighbors detail']],
				//на команду не хватило прав - это должно быть видно явно
				'arp' => ['status' => 'denied', 'commands' => ['show ip arp'],
					'note' => 'коммутатор отверг команду: % Invalid input detected'],
				'lag' => ['status' => 'ok', 'commands' => ['show etherchannel summary']],
				'interfaces' => ['status' => 'empty', 'commands' => ['show interfaces status']],
			]],
			'snmp' => ['available' => false, 'note' => 'коммутатор не ответил по SNMP',
				'capabilities' => [
					'interfaces' => ['status' => 'error', 'note' => 'коммутатор не ответил по SNMP',
						'commands' => ['SNMP IF-MIB: ifName/ifDescr, статусы, скорость']],
				]],
		]];
		$provider = $this->makeProvider([$this->response($payload)]);

		$html = $provider->renderSwitchPanel($switch);

		$this->assertStringContainsString('диагностика опроса', $html);
		$this->assertStringContainsString('show mac address-table', $html);
		//отказ по правам - свой значок и ответ коммутатора в подсказке
		$this->assertStringContainsString('fa-ban', $html);
		$this->assertStringContainsString('отверг команду', $html);
		$this->assertStringContainsString('LLDP включён', $html);
		//недоступный транспорт назван с причиной
		$this->assertStringContainsString('не ответил по SNMP', $html);
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

	/** Сбойный опрос не выносит вердиктов: записанные связи не помечаются под снятие */
	public function testFailedScanDrawsNoVerdicts()
	{
		$switch = $this->makeSwitch(['ip' => '10.50.2.16', 'ports_override' => "Gi1/0/1
Gi1/0/2"]);
		$arm = new Techs();
		$arm->setAttributes(['model_id' => $switch->model_id, 'num' => 'ARM-LINKED',
			'history' => ''], false);
		$this->assertTrue($arm->save(false));
		//записанная связь Gi1/0/1 <-> порт АРМа
		$mine = new \app\models\Ports(['techs_id' => $switch->id, 'name' => 'Gi1/0/1', 'comment' => '']);
		$this->assertTrue($mine->save(false));
		$his = new \app\models\Ports(['techs_id' => $arm->id, 'name' => 'eth0', 'comment' => '']);
		$this->assertTrue($his->save(false));
		$mine->link_ports_id = $his->id;
		$this->assertTrue($mine->save(false));

		$provider = $this->makeProvider([$this->response($this->payload([], [
			'mode' => 'table',
			'targets' => ['requested' => 1, 'answered' => 0, 'failed' => 1],
			'errors' => [['target' => $switch->id, 'host' => '10.50.2.16',
				'error' => 'не уложился в срок опроса (240 с)']],
		]))]);
		$html = $provider->renderSwitchPanel($switch);

		//причина видна, а слоя сверки нет: таблица как в карточке без опроса
		$this->assertStringContainsString('не уложился в срок опроса', $html);
		$this->assertStringNotContainsString('линка нет', $html);
		$this->assertStringNotContainsString('не отозвалось', $html);
		//связь по-прежнему показана как записанная, без пометок
		$this->assertStringContainsString('ARM-LINKED', $html);
	}
}
