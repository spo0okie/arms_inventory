<?php

namespace tests\unit\components;

use app\components\integrations\providers\MacSearchProvider;
use app\components\NetworkMap;
use app\models\Manufacturers;
use app\models\Places;
use app\models\Ports;
use app\models\TechModels;
use app\models\Techs;
use app\models\TechTypes;
use Codeception\Test\Unit;
use Yii;

/**
 * Карта сети площадки (docs/dev/network-map.md): граф из записей и слой
 * сверки с LLDP.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class NetworkMapTest extends Unit
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
		$this->model = null;
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) $this->transaction->rollBack();
	}

	private function makeSite(): Places
	{
		$place = new Places();
		$place->setAttributes(['name' => 'Площадка '.uniqid(), 'short' => substr(uniqid(), -6),
			'parent_id' => null, 'comment' => ''], false);
		$this->assertTrue($place->save(false));
		return $place;
	}

	/** Коммутатор площадки с объявленными портами и адресом */
	/** @var TechModels|null модель коммутатора на один тест (откатывается с транзакцией) */
	private $model = null;

	private function makeSwitch(Places $site, string $ip, string $ports, array $attrs = []): Techs
	{
		$model = &$this->model;
		if (!is_object($model)) {
			$manufacturer = new Manufacturers();
			$manufacturer->setAttributes(['name' => 'Вендор '.uniqid(), 'comment' => ''], false);
			$this->assertTrue($manufacturer->save(false));
			$type = TechTypes::findOne(['code' => 'net_switch']);
			$this->assertNotNull($type, 'в дампе нужен тип net_switch');
			$model = new TechModels();
			$model->setAttributes(['name' => 'Модель '.uniqid(), 'type_id' => $type->id,
				'manufacturers_id' => $manufacturer->id, 'ports' => '', 'comment' => ''], false);
			$this->assertTrue($model->save(false));
		}
		$tech = new Techs();
		$tech->setAttributes(array_merge(['model_id' => $model->id, 'num' => 'SW-'.uniqid(),
			'ip' => $ip, 'places_id' => $site->id, 'ports_override' => $ports, 'history' => ''], $attrs), false);
		$this->assertTrue($tech->save(false));
		$tech->refresh();
		return $tech;
	}

	/** Связь порт↔порт между двумя коммутаторами */
	private function link(Techs $a, string $portA, Techs $b, string $portB, string $aggr = ''): Ports
	{
		$port = Ports::forTech($a, $portA);
		$port->aggr = $aggr ?: null;
		$port->link_techs_id = $b->id;
		$port->link_ports_id = 'create:'.$portB;
		$this->assertTrue($port->save(), implode('; ', $port->firstErrors));
		if ($aggr) {
			$peer = $port->linkPort;
			$peer->aggr = $aggr;
			$this->assertTrue($peer->save());
		}
		return $port;
	}

	/** Граф из записей: узлы - коммутаторы площадки, рёбра - связи их портов, группа - одно ребро */
	public function testGraphFromRecords()
	{
		$site = $this->makeSite();
		$core = $this->makeSwitch($site, '10.60.0.1', "Gi1/0/1\nGi1/0/2\nGi1/0/3");
		$edge = $this->makeSwitch($site, '10.60.0.2', "Gi1/0/47\nGi1/0/48");
		$other = $this->makeSwitch($this->makeSite(), '10.60.9.1', 'Gi1/0/1');
		//группа из двух кабелей между core и edge, связь с другой площадкой и с сервером
		$this->link($core, 'Gi1/0/1', $edge, 'Gi1/0/47', 'Po1');
		$this->link($core, 'Gi1/0/2', $edge, 'Gi1/0/48', 'Po1');
		$this->link($core, 'Gi1/0/3', $other, 'Gi1/0/1');

		$map = new NetworkMap($site);

		$this->assertSame([$core->id, $edge->id], array_keys($map->nodes));
		$this->assertCount(1, $map->edges, 'группа портов - одно ребро');
		$edgeData = reset($map->edges);
		$this->assertSame('Po1', $edgeData['aggr']);
		$this->assertCount(2, $edgeData['links']);
		//связь на чужую площадку - край карты, не ребро
		$this->assertCount(1, $map->outside);
		$this->assertSame(3, $map->nodes[$core->id]['used']);

		//связь с сервером - не тема карты: ни ребро, ни «край карты», иначе
		//на боевой площадке это портянка из всех патч-кордов разом
		$serverModel = new TechModels();
		$serverModel->setAttributes(['name' => 'Сервер '.uniqid(),
			'manufacturers_id' => $this->model->manufacturers_id, 'ports' => 'eth0',
			'comment' => ''], false);
		$this->assertTrue($serverModel->save(false));
		$server = new Techs();
		$server->setAttributes(['model_id' => $serverModel->id, 'num' => 'SRV-'.uniqid(),
			'places_id' => $site->id, 'history' => ''], false);
		$this->assertTrue($server->save(false));
		$this->link($edge, 'Gi1/0/48', $server, 'eth0');

		$again = new NetworkMap($site);
		$this->assertCount(1, $again->outside, 'сервер в аплинках не появился');
		$this->assertCount(1, $again->edges);

		$text = $map->mermaid();
		$this->assertStringContainsString('Po1 ×2', $text);
		$this->assertStringContainsString('click n'.$core->id, $text);
	}

	/** «Учитывать помещения»: узлы заворачиваются в рамки своих помещений */
	public function testGroupByPlace()
	{
		$site = $this->makeSite();
		$room = new Places();
		$room->setAttributes(['name' => 'Серверная '.uniqid(), 'short' => substr(uniqid(), -6),
			'parent_id' => $site->id, 'comment' => ''], false);
		$this->assertTrue($room->save(false));

		//core стоит прямо на корне площадки - «своего» помещения у него нет
		$core = $this->makeSwitch($site, '10.60.0.1', 'Gi1/0/1');
		$inRoom = $this->makeSwitch($site, '10.60.0.2', 'Gi1/0/1', ['places_id' => $room->id]);

		$map = new NetworkMap($site);
		$map->groupByPlace = true;
		$text = $map->mermaid();

		$this->assertStringContainsString('subgraph p'.$room->id, $text);
		$this->assertStringContainsString($room->name, $text);
		//позиции: узел помещения - между subgraph и end, узел с корня - вне рамки
		$sub = strpos($text, 'subgraph p'.$room->id);
		$end = strpos($text, 'end', $sub);
		$inside = strpos($text, 'n'.$inRoom->id.'["');
		$outside = strpos($text, 'n'.$core->id.'["');
		$this->assertTrue($sub < $inside && $inside < $end, 'узел помещения в рамке');
		//узел на корне площадки остаётся вне рамок: рамка вокруг всей площадки
		//ничего не говорит
		$this->assertFalse($sub < $outside && $outside < $end, 'узел с корня вне рамки');
		//без флага рамок нет вовсе
		$map->groupByPlace = false;
		$this->assertStringNotContainsString('subgraph', $map->mermaid());
	}

	/** Стек (общий IP) - один узел, связь внутри стека не рисуется */
	public function testStackIsOneNode()
	{
		$site = $this->makeSite();
		$first = $this->makeSwitch($site, '10.60.0.1', "Gi1/0/1\nGi1/0/2");
		$second = $this->makeSwitch($site, '10.60.0.1', "Gi2/0/1\nGi2/0/2");
		$edge = $this->makeSwitch($site, '10.60.0.2', 'Gi1/0/48');
		$this->link($first, 'Gi1/0/2', $second, 'Gi2/0/2');   //внутри стека
		$this->link($second, 'Gi2/0/1', $edge, 'Gi1/0/48');

		$map = new NetworkMap($site);
		$this->assertCount(2, $map->nodes);
		$this->assertStringContainsString(' + ', $map->nodes[$first->id]['name']);
		$this->assertCount(1, $map->edges);
	}

	/**
	 * Слой сверки: подтверждённое, невидимое, найденное (с конфликтом на
	 * стороне соседа), неопознанное. LLDP симметричен - одна связь с двух
	 * сторон считается один раз.
	 */
	public function testOverlay()
	{
		$site = $this->makeSite();
		$core = $this->makeSwitch($site, '10.60.0.1', "Gi1/0/1\nGi1/0/2\nGi1/0/3");
		$edge = $this->makeSwitch($site, '10.60.0.2', "Gi1/0/47\nGi1/0/48");
		$third = $this->makeSwitch($site, '10.60.0.3', 'Gi1/0/1');
		$this->link($core, 'Gi1/0/1', $edge, 'Gi1/0/47');   //подтвердится
		$this->link($core, 'Gi1/0/2', $third, 'Gi1/0/1');   //LLDP не увидит
		$edge->refresh();

		$provider = new MacSearchProvider(['id' => 'macsearch', 'config' => ['url' => 'http://x', 'token' => 't']]);
		$map = new NetworkMap($site);
		$row = fn(Techs $from, string $port, string $remoteIp, string $remotePort) => [
			'target' => $from->id, 'port' => $port, 'remote_mac' => '', 'remote_name' => $remoteIp,
			'remote_port' => $remotePort, 'protocol' => 'lldp'];
		$map->overlay(['status' => 'done', 'rows' => [
			$row($core, 'Gi1/0/1', '10.60.0.2', 'GigabitEthernet1/0/47'),   //имя в другой нотации
			$row($edge, 'Gi1/0/47', '10.60.0.1', 'Gi1/0/1'),                 //та же связь с той стороны
			//новая: core Gi1/0/3 <-> edge Gi1/0/48, но Gi1/0/48 у edge... свободен
			$row($core, 'Gi1/0/3', '10.60.0.2', 'Gi1/0/48'),
			//неопознанный сосед
			$row($third, 'Gi1/0/1', 'sw-unknown', 'Gi0/1'),
		], 'errors' => []], $provider);

		$o = $map->overlay;
		$this->assertCount(1, $o['confirmed']);
		$this->assertCount(1, $o['unseen'], 'core-third записано, но third про core молчит');
		$this->assertCount(1, $o['found'], 'связь с двух сторон - одна находка');
		$this->assertSame('Gi1/0/3', $o['found'][0]['port']);
		$this->assertSame('Gi1/0/48', $o['found'][0]['peer']['name']);
		$this->assertNull($o['found'][0]['conflict']);
		$this->assertCount(1, $o['unknown']);
		$this->assertStringContainsString('-.-', $map->mermaid());

		//конфликт на стороне соседа: edge Gi1/0/47 уже записан на core Gi1/0/1,
		//а LLDP говорит, что туда воткнут core Gi1/0/3
		$map2 = new NetworkMap($site);
		$map2->overlay(['status' => 'done', 'rows' => [
			$row($core, 'Gi1/0/3', '10.60.0.2', 'Gi1/0/47'),
		], 'errors' => []], $provider);
		$found = $map2->overlay['found'][0];
		$this->assertNotNull($found['conflict_remote']);
		$this->assertSame($core->id, (int)$found['conflict_remote']->techs_id);
	}

	/**
	 * На карте и в целях - только работающее: у статуса флаг operating.
	 * Оборудование без статуса считается работающим (не знаем - не выкидываем).
	 */
	public function testOperatingFilter()
	{
		$site = $this->makeSite();
		$working = $this->makeSwitch($site, '10.60.0.1', 'Gi1/0/1');
		$stored = $this->makeSwitch($site, '10.60.0.2', 'Gi1/0/1',
			['state_id' => \app\models\TechStates::find()
				->where(['operating' => 0])->select('id')->scalar()]);
		$this->assertNotEmpty($stored->state_id, 'в дампе нужен статус без operating');

		$map = new NetworkMap($site);
		$this->assertSame([$working->id], array_keys($map->nodes), 'складское - не узел карты');

		$provider = new MacSearchProvider(['id' => 'macsearch',
			'config' => ['url' => 'http://x', 'token' => 't']]);
		$targets = $provider->targets([$site->id]);
		$this->assertSame([$working->id], array_column($targets, 'id'), 'складское - не цель опроса');
	}

	/**
	 * Шум сверки: телефоны отсеиваются шаблоном, записи без имени и адреса -
	 * как безличные, повторы одного соседа схлопываются со счётчиком. Все
	 * фильтры считаются, а не молчат.
	 */
	public function testOverlayNoise()
	{
		$site = $this->makeSite();
		$core = $this->makeSwitch($site, '10.60.0.1', "Gi1/0/1\nGi1/0/2");

		$provider = new MacSearchProvider(['id' => 'macsearch',
			'config' => ['url' => 'http://x', 'token' => 't']]);
		$map = new NetworkMap($site);
		$rows = [
			//телефоны: Cisco SEP<MAC>, SIP<MAC>, «IP Phone» в имени
			['target' => $core->id, 'port' => 'Gi1/0/1', 'remote_mac' => '',
				'remote_name' => 'SEP00DA55B88A3B', 'remote_port' => 'Port 1', 'protocol' => 'cdp'],
			['target' => $core->id, 'port' => 'Gi1/0/1', 'remote_mac' => '',
				'remote_name' => 'Cisco IP Phone SPA504G', 'remote_port' => '00da.55b8.8a3b', 'protocol' => 'lldp'],
			//безличная запись - чинить не по чему
			['target' => $core->id, 'port' => 'Gi1/0/2', 'remote_mac' => '',
				'remote_name' => '', 'remote_port' => '', 'protocol' => 'lldp'],
			//один сосед три раза (timeMark старых прошивок)
			['target' => $core->id, 'port' => 'Gi1/0/2', 'remote_mac' => '',
				'remote_name' => 'sw-lost', 'remote_port' => 'Gi0/1', 'protocol' => 'lldp'],
			['target' => $core->id, 'port' => 'Gi1/0/2', 'remote_mac' => '',
				'remote_name' => 'sw-lost', 'remote_port' => 'Gi0/1', 'protocol' => 'lldp'],
			['target' => $core->id, 'port' => 'Gi1/0/2', 'remote_mac' => '',
				'remote_name' => 'sw-lost', 'remote_port' => 'Gi0/1', 'protocol' => 'lldp'],
		];
		$map->overlay(['status' => 'done', 'rows' => $rows, 'errors' => []], $provider);

		$o = $map->overlay;
		$this->assertSame(2, $o['ignored']);
		$this->assertSame(1, $o['noise']);
		$this->assertCount(1, $o['unknown']);
		$this->assertSame(3, $o['unknown'][0]['count']);
		//на схеме один узел «?», а не по узлу на запись
		$this->assertSame(1, substr_count($map->mermaid(), '(["? '));
	}

	/** Имя порта по LLDP -> объявленный порт: точное, ключ, иначе кандидаты */
	public function testResolvePortName()
	{
		$site = $this->makeSite();
		$switch = $this->makeSwitch($site, '10.60.0.1', "Gi1/0/1\nTe1/0/1\nGi1/0/2");

		$this->assertSame('Gi1/0/2', MacSearchProvider::resolvePortName($switch, 'GigabitEthernet1/0/2')['name']);
		//Gi1/0/1 и Te1/0/1 по ключу неразличимы - человеку
		$ambiguous = MacSearchProvider::resolvePortName($switch, 'Ethernet1/0/1');
		$this->assertNull($ambiguous['name']);
		$this->assertCount(3, $ambiguous['candidates']);
		//точное имя побеждает ключ
		$this->assertSame('Te1/0/1', MacSearchProvider::resolvePortName($switch, 'Te1/0/1')['name']);
	}
}
