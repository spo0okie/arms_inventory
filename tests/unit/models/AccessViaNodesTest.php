<?php

namespace tests\unit\models;

use app\models\AccessTypes;
use app\models\Aces;
use app\models\Services;
use Codeception\Test\Unit;
use Yii;

/**
 * Связь «узел ↔ сервис на нём» в доступах (docs/dev/access-chains.md, §3–4):
 *  - входящие сервиса через его узлы — только соединения, вписывающиеся в стандартные
 *    доступы сервиса по протоколу/порту (у проброса — порт назначения);
 *  - кандидаты в хопы: от проброса на сервер — к записям сервисов этого сервера и обратно.
 *
 * Опора на демо-данные:
 *   ACE 9100 (11-forwards): 55.66.77.81 → ОС MSK-PROXY (34), HTTPS 9001 «TCP 443->8443», проброс
 *   сервис 19 «Контроль доступа в интернет» работает на ОС 34, стандартный доступ — тип 2 «TCP 3128»
 *   ACE 9202 (12-transit): сервис 19 → сервис 18 (1С), HTTPS TCP 443
 *   ACE 9200 (12-transit): сервис 24 → сервис 19
 *
 * Данные меняются в транзакции и откатываются.
 */
class AccessViaNodesTest extends Unit
{
	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		AccessTypes::invalidateAllItemsCache();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) $this->transaction->rollBack();
		AccessTypes::invalidateAllItemsCache();
	}

	private function viaNodes(int $serviceId): array
	{
		return Services::findOne($serviceId)->getIncomingViaNodesAcesIds();
	}

	private function addDefault(int $serviceId, int $typeId, string $params): void
	{
		Yii::$app->db->createCommand()->insert('default_access_in_services', [
			'services_id'=>$serviceId, 'access_types_id'=>$typeId, 'ip_params'=>$params,
		])->execute();
	}

	public function testLandingRulesOfForwardUseDestination()
	{
		$rules=Aces::findOne(9100)->getLandingRules();
		$this->assertSame([['protocols'=>['TCP'],'ports'=>[[8443,8443]]]], $rules[9001],
			'проброс 443->8443 приходит на узел в TCP 8443');
	}

	public function testForwardNotShownForServiceOnOtherPort()
	{
		$this->assertNotContains(9100, $this->viaNodes(19),
			'сервис слушает 3128, проброс приходит на 8443 — не его соединение');
	}

	public function testForwardShownForServiceOnMatchingPort()
	{
		$this->addDefault(19, 9001, 'TCP 8443');
		$this->assertContains(9100, $this->viaNodes(19),
			'стандартный HTTPS сервиса на 8443 — проброс на сервер виден у сервиса');
	}

	public function testServiceWithoutDefaultsGetsNothing()
	{
		Yii::$app->db->createCommand()->delete('default_access_in_services', ['services_id'=>19])->execute();
		$this->assertSame([], $this->viaNodes(19));
	}

	public function testAcesSearchIncomingTabIncludesMatchingForward()
	{
		$this->addDefault(19, 9001, 'TCP 8443');
		$ids=array_map('intval',(new \app\models\AcesSearch())->search(['AcesSearch'=>[
			'services_resource_ids'=>[19],
			'services_nodes_resource_ids'=>[19],
		]])->query->select('aces.id')->column());
		$this->assertContains(9100, $ids, 'проброс на сервер — во вкладке «Доступы сюда»');
		$this->assertContains(9200, $ids, 'доступ к самому сервису — там же, как и раньше');
	}

	public function testForwardToServerOffersServiceHopsAsNext()
	{
		$this->assertArrayHasKey(9202, Aces::findOne(9100)->nextCandidates(),
			'после проброса на сервер прокси — записи сервиса, работающего на нём');
	}

	public function testServiceHopOffersForwardToItsServerAsPrev()
	{
		$this->assertArrayHasKey(9100, Aces::findOne(9202)->prevCandidates(),
			'перед записью сервиса — проброс на его сервер');
	}

	public function testUnsavedServiceHopOffersForwardAsPrev()
	{
		//форма «добавить исходящий доступ» со страницы сервиса: субъект подставлен, записи в БД ещё нет
		$ace=new Aces();
		$ace->services_ids=[19];
		$this->assertArrayHasKey(9100, $ace->prevCandidates(),
			'кандидаты считаются по введённым субъектам, а не по связям в БД');
	}

	public function testUnsavedIpSubjectOffersAccessesToItAsPrev()
	{
		//адрес-субъект у новой записи есть только текстом; существующий находится, новый не создаётся
		$ace=new Aces();
		$ace->ips='10.0.0.2';
		$this->assertArrayHasKey(9101, $ace->prevCandidates(), 'проброс на 10.0.0.2 — предыдущий хоп');
		$before=(int)\app\models\NetIps::find()->count();
		(new Aces(['ips'=>'10.123.45.67']))->prevCandidates();
		$this->assertSame($before, (int)\app\models\NetIps::find()->count(), 'подбор кандидатов адресов не заводит');
	}

	public function testServiceResourceDoesNotClimbToServerNeighbours()
	{
		$db=Yii::$app->db;
		//сосед по серверу: другой сервис на ОС 34 и его исходящая запись
		$neighbour=new Services(['name'=>'HAProxy '.uniqid(), 'is_service'=>1]);
		$this->assertTrue($neighbour->save(false));
		$db->createCommand()->insert('comps_in_services', ['comps_id'=>34, 'services_id'=>$neighbour->id])->execute();
		$db->createCommand()->insert('aces', ['acls_id'=>9201, 'name'=>'сосед по серверу', 'ips'=>''])->execute();
		$aceId=(int)$db->getLastInsertID();
		$db->createCommand()->insert('services_in_aces', ['aces_id'=>$aceId, 'services_id'=>$neighbour->id])->execute();

		$this->assertArrayNotHasKey($aceId, Aces::findOne(9200)->nextCandidates(),
			'ресурс — сервис: соседей по его серверам в кандидаты не тянем');
		$this->assertArrayHasKey($aceId, Aces::findOne(9100)->nextCandidates(),
			'ресурс — сервер: кандидаты — все сервисы сервера, человек выбирает сам');
	}

	public function testJointOfForwardAndServiceHop()
	{
		$this->assertTrue(Aces::transitJoint(Aces::findOne(9100), Aces::findOne(9202)),
			'сервер прокси — узел сервиса-субъекта следующего хопа: стык сходится');
	}
}
