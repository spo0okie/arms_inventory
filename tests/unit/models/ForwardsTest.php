<?php

namespace tests\unit\models;

use app\models\AccessTypes;
use app\models\Aces;
use app\models\Comps;
use app\models\DnsNames;
use app\models\NetIps;
use Codeception\Test\Unit;
use Yii;

/**
 * Пробросы (NAT) через записи доступа (plans/access-chains.md, итерация 2):
 * форвард — обычный ACE с типом доступа, помеченным is_forward; субъект — адрес
 * входа, ресурс ACL — узел назначения или его адрес, параметры «TCP 443->8443».
 *
 * Опора на демо-данные (tests/_data/demo-seed/11-forwards.sql):
 *   ACE 9100: 55.66.77.81 (ip 26) → ОС MSK-PROXY (34), TCP 443->8443
 *   ACE 9101: 55.66.77.81 (ip 26) → адрес 10.0.0.2 (ip 30, ОС MSK-OVPN 20), UDP 1194
 *   ACE 9102: 66.77.88.98 (ip 24) → адрес 10.0.0.1 (ip 31, ОС chl-ovpn), UDP 1194
 * и имена этапа 10: vpn.taburetka.ru (9004) → оба белых адреса.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class ForwardsTest extends Unit
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
		AccessTypes::invalidateAllItemsCache();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		AccessTypes::invalidateAllItemsCache();
	}

	/** @param Aces[] $aces */
	private function ids(array $aces): array
	{
		$ids = array_map(static fn(Aces $ace) => (int)$ace->id, $aces);
		sort($ids);
		return $ids;
	}

	// --- разбор параметров --------------------------------------------------

	public function testParseForwardParams()
	{
		$this->assertSame(['TCP 443', '8443'], Aces::parseForwardParams('TCP 443->8443'));
		$this->assertSame(['TCP 443', '8443'], Aces::parseForwardParams(' TCP 443  ->  8443 '), 'пробелы вокруг стрелки');
		$this->assertSame(['TCP 443', '8443'], Aces::parseForwardParams('TCP 443→8443'), 'юникодная стрелка');
		$this->assertSame(['TCP 443', '8443'], Aces::parseForwardParams('TCP 443=>8443'));
		$this->assertSame(['UDP 1194', ''], Aces::parseForwardParams('UDP 1194'), 'без стрелки порт не транслируется');
		$this->assertSame(['', ''], Aces::parseForwardParams(''));
	}

	public function testForwardRulesOfAce()
	{
		$ace = Aces::findOne(9100);
		$this->assertTrue($ace->hasForwardAccess());
		$rules = $ace->forwardRules;
		$this->assertCount(1, $rules);
		$this->assertSame('TCP 443', $rules[0]['ext']);
		$this->assertSame('8443', $rules[0]['int']);

		//обычный доступ (RDP/HTTPS из этапа 07) — не проброс
		$plain = Aces::findOne(9001);
		$this->assertFalse($plain->hasForwardAccess());
		$this->assertSame([], $plain->forwardRules);
	}

	// --- тип доступа --------------------------------------------------------

	public function testForwardFlagIsCollectedFromChildTypes()
	{
		$this->assertContains(9100, AccessTypes::forwardTypeIds());

		//комплексный тип, включающий проброс, — тоже форвард-тип
		$bundle = new AccessTypes(['code' => 'fwd-bundle', 'name' => 'Публикация (комплект)']);
		$bundle->children_ids = [9100];
		$this->assertTrue($bundle->save(), print_r($bundle->errors, true));
		AccessTypes::invalidateAllItemsCache();

		$this->assertFalse((bool)$bundle->is_forward, 'собственный флаг не выставлен');
		$this->assertContains((int)$bundle->id, AccessTypes::forwardTypeIds(), 'но проброс включён через дочерний тип');
		$this->assertNotContains(9001, AccessTypes::forwardTypeIds(), 'HTTPS — не проброс');
	}

	// --- выборки пробросов --------------------------------------------------

	public function testNodeSeesForwardsToItselfAndToItsIps()
	{
		//ресурс ACL — сама ОС
		$this->assertSame([9100], $this->ids(Comps::findOne(34)->forwardsIn));
		//ресурс ACL — адрес ОС
		$this->assertSame([9101], $this->ids(Comps::findOne(20)->forwardsIn));
		//ОС без пробросов
		$this->assertSame([], Comps::findOne(16)->forwardsIn);
	}

	public function testIpSeesBothDirections()
	{
		$white = NetIps::findOne(26);
		$this->assertSame([9100, 9101], $this->ids($white->forwardsOut), 'белый адрес — вход двух пробросов');
		$this->assertSame([], $white->forwardsIn);

		$grey = NetIps::findOne(30);
		$this->assertSame([9101], $this->ids($grey->forwardsIn), 'серый адрес доступен снаружи');
		$this->assertSame([], $grey->forwardsOut);

		//адрес узла, на который проброс заведён по узлу (а не по адресу), тоже его видит
		$proxyIp = NetIps::findOne(39);
		$this->assertSame([9100], $this->ids($proxyIp->forwardsIn));
	}

	public function testDnsNameChainGoesThroughAllItsIps()
	{
		//vpn.taburetka.ru указывает на оба белых адреса → три проброса
		$this->assertSame([9100, 9101, 9102], $this->ids(DnsNames::findOne(9004)->forwardsOut));
		//имя без адресов никуда не ведёт
		$this->assertSame([], DnsNames::findOne(9005)->forwardsOut);
	}

	public function testArchivedForwardIsHidden()
	{
		//ОС назначения ушла в архив — доступ к мёртвому ресурсу мёртв, проброс не показываем
		Yii::$app->db->createCommand()->update('comps', ['archived' => 1], ['id' => 34])->execute();
		$this->assertSame([9101], $this->ids(Aces::findForwardsFrom([26])));
	}

	public function testNoForwardTypesMeansNoQueriesAndNoForwards()
	{
		Yii::$app->db->createCommand()->update('access_types', ['is_forward' => 0])->execute();
		AccessTypes::invalidateAllItemsCache();
		$this->assertSame([], AccessTypes::forwardTypeIds());
		$this->assertSame([], Aces::findForwardsFrom([26]));
		$this->assertSame([], Aces::findForwardsTo([34], [], [30]));
	}
}
