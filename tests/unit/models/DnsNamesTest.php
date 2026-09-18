<?php

namespace tests\unit\models;

use app\models\DnsNames;
use app\models\Domains;
use app\models\NetIps;
use Codeception\Test\Unit;
use Yii;

/**
 * DNS-имена (plans/access-chains.md, итерация 1): разбор FQDN-ввода по суффиксу
 * зоны, связь имени с адресами через реестр NetIps и «сторож пустоты» адреса —
 * IP, на который указывает имя, не удаляется сам, а ставший ничейным — удаляется.
 *
 * Опора на демо-данные: зоны taburetka.local (1) и taburetka.ru (9000, внешняя),
 * имена 9000..9012 (tests/_data/demo-seed/10-dns-names.sql); адрес 10.20.75.20
 * привязан к ОС msk-inventory.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class DnsNamesTest extends Unit
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
		//разбор зон идёт по общему кэшу справочника — тест не должен зависеть от того,
		//что в него положили предыдущие тесты
		Domains::invalidateAllItemsCache();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		Domains::invalidateAllItemsCache();
	}

	private function makeName(array $attrs): DnsNames
	{
		$name = new DnsNames();
		$name->setAttributes($attrs, false);
		$this->assertTrue($name->save(), print_r($name->errors, true));
		return $name;
	}

	// --- разбор имени ------------------------------------------------------

	public function testFqdnInputSplitsIntoZoneAndHost()
	{
		$name = new DnsNames(['host' => 'WWW2.Taburetka.RU']);
		$this->assertTrue($name->validate(), print_r($name->errors, true));
		$this->assertSame(9000, (int)$name->domain_id, 'зона определилась по суффиксу');
		$this->assertSame('www2', $name->host, 'в имени остался только host, в нижнем регистре');
		$this->assertSame('www2.taburetka.ru', $name->fqdn);
	}

	public function testLongestZoneWins()
	{
		//lab1.taburetka.local (2) вложена в taburetka.local (1): берётся самая длинная
		$name = new DnsNames(['host' => 'srv.lab1.taburetka.local']);
		$this->assertTrue($name->validate(), print_r($name->errors, true));
		$this->assertSame(2, (int)$name->domain_id);
		$this->assertSame('srv', $name->host);
	}

	public function testZoneFqdnItselfIsApex()
	{
		$name = new DnsNames(['host' => 'lab1.taburetka.local']);
		$this->assertTrue($name->validate(), print_r($name->errors, true));
		$this->assertSame(2, (int)$name->domain_id);
		$this->assertSame('', $name->host, 'имя, равное fqdn зоны, — apex');
		$this->assertTrue($name->isApex);
		$this->assertSame('lab1.taburetka.local', $name->fqdn);
	}

	public function testUnknownSuffixStaysInChosenZone()
	{
		//точки внутри имени допустимы: a.b в выбранной зоне, если суффикс не совпал ни с одной зоной
		$name = new DnsNames(['host' => 'a.b', 'domain_id' => 1]);
		$this->assertTrue($name->validate(), print_r($name->errors, true));
		$this->assertSame(1, (int)$name->domain_id);
		$this->assertSame('a.b', $name->host);
	}

	public function testHostIsUniqueInZone()
	{
		$dupe = new DnsNames(['host' => 'www', 'domain_id' => 9000]);
		$this->assertFalse($dupe->validate(), 'www.taburetka.ru уже есть в демо-данных');
		$this->assertArrayHasKey('host', $dupe->errors);

		//то же имя в другой зоне — можно
		$other = new DnsNames(['host' => 'www', 'domain_id' => 1]);
		$this->assertTrue($other->validate(), print_r($other->errors, true));
	}

	public function testZoneIsRequiredWhenNotResolvable()
	{
		$name = new DnsNames(['host' => 'nowhere']);
		$this->assertFalse($name->validate());
		$this->assertArrayHasKey('domain_id', $name->errors);
	}

	// --- поиск по имени -----------------------------------------------------

	public function testFindByNameResolvesFqdnAndBareHost()
	{
		$this->assertSame(9001, (int)DnsNames::findByName('www.taburetka.ru')->id);
		$this->assertSame(9000, (int)DnsNames::findByName('taburetka.ru')->id, 'apex по fqdn зоны');
		$this->assertSame(9010, (int)DnsNames::findByName('inventory')->id, 'host без зоны ищется по всем зонам');
		$this->assertNull(DnsNames::findByName('no-such-name.taburetka.ru'));
	}

	// --- адреса -------------------------------------------------------------

	public function testIpsAreAttachedThroughRegistryAndCreatedOnDemand()
	{
		$this->assertNull(NetIps::findOne(['text_addr' => '198.51.100.7']), 'адреса ещё нет в реестре');

		$name = $this->makeName([
			'domain_id' => 9000, 'host' => 'test-attach',
			'ip' => "10.20.75.20\n198.51.100.7",
		]);
		$name->refresh();

		$addrs = array_map(static fn(NetIps $ip) => $ip->text_addr, $name->netIps);
		sort($addrs);
		$this->assertSame(['10.20.75.20', '198.51.100.7'], $addrs, 'имя указывает на оба адреса');
		$this->assertNotNull(NetIps::findOne(['text_addr' => '198.51.100.7']), 'новый адрес создан в реестре');
	}

	public function testDetachedOrphanIpIsDeletedButBoundIpStays()
	{
		$name = $this->makeName([
			'domain_id' => 9000, 'host' => 'test-detach',
			'ip' => "10.20.75.20\n198.51.100.8",
		]);
		$orphanId = NetIps::findOne(['text_addr' => '198.51.100.8'])->id;
		$boundId = NetIps::findOne(['text_addr' => '10.20.75.20'])->id;

		//убираем оба адреса из имени
		$name->ip = '';
		$this->assertTrue($name->save(), print_r($name->errors, true));
		$name->refresh();

		$this->assertCount(0, $name->netIps);
		$this->assertNull(NetIps::findOne($orphanId), 'ничейный адрес удалился из реестра');
		$this->assertNotNull(NetIps::findOne($boundId), 'адрес, привязанный к ОС, остался');
	}

	public function testDeleteIfEmptyKeepsIpReferencedByDnsName()
	{
		$name = $this->makeName([
			'domain_id' => 9000, 'host' => 'test-guard', 'ip' => '198.51.100.9',
		]);
		$ip = NetIps::findOne(['text_addr' => '198.51.100.9']);
		$this->assertNotNull($ip);

		$ip->deleteIfEmpty();
		$this->assertNotNull(NetIps::findOne($ip->id), 'адрес, на который указывает имя, «сторож пустоты» не удаляет');

		//удалили имя — адрес стал ничейным и ушёл вместе со связью
		$this->assertNotFalse($name->delete());
		$this->assertNull(NetIps::findOne($ip->id), 'после удаления имени ничейный адрес удалён');
		$this->assertSame(0, (int)Yii::$app->db->createCommand(
			'SELECT COUNT(*) FROM dns_names_in_ips WHERE dns_names_id=:id', [':id' => $name->id]
		)->queryScalar(), 'junction-строк не осталось');
	}

	public function testDeletingNameKeepsIpBoundElsewhere()
	{
		$name = $this->makeName(['domain_id' => 1, 'host' => 'test-alias', 'ip' => '10.20.75.20']);
		$boundId = NetIps::findOne(['text_addr' => '10.20.75.20'])->id;
		$this->assertNotFalse($name->delete());
		$this->assertNotNull(NetIps::findOne($boundId), 'адрес ОС msk-inventory остался после удаления алиаса');
	}

	// --- обратная сторона и зона --------------------------------------------

	public function testIpKnowsItsDnsNamesAndZoneKnowsItsNames()
	{
		$ip = NetIps::findOne(['text_addr' => '55.66.77.90']);
		$hosts = array_map(static fn(DnsNames $n) => $n->fqdn, $ip->dnsNames);
		sort($hosts);
		$this->assertSame(['taburetka.ru', 'www.taburetka.ru'], $hosts, 'у адреса видны оба имени, включая apex');

		$zone = Domains::findOne(9000);
		$this->assertCount(6, $zone->dnsNames, 'все имена внешней зоны из демо-сида');
		$this->assertContains(9005, array_map(static fn($n) => (int)$n->id, $zone->dnsNames), 'имя без адресов тоже в зоне');
	}
}
