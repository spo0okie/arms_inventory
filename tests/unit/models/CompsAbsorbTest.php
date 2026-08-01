<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\Domains;
use app\models\Services;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты поглощения одной ОС (Comps) другой: CompsController::actionAbsorb -> Comps::absorbComp.
 *
 * Сценарий из UI: на форме view ОС без домена (но с привязанным сервисом)
 * показывается клон с доменом (но без сервиса). После поглощения клона ожидается,
 * что выжившая ОС получит домен клона (absorb=ifEmpty) и сохранит свой сервис.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class CompsAbsorbTest extends Unit
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
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function makeDomain(): Domains
	{
		$domain = new Domains();
		$domain->setAttributes([
			'name' => 'absorbtst',
			'fqdn' => 'absorbtst.local',
			'comment' => 'домен для теста поглощения',
		], false);
		$this->assertTrue($domain->save(), print_r($domain->errors, true));
		return $domain;
	}

	private function makeService(): Services
	{
		$service = new Services();
		$service->setAttributes([
			'name' => 'absorb-test-service',
			'description' => 'сервис для теста поглощения',
			'is_end_user' => 0,
		], false);
		$this->assertTrue($service->save(), print_r($service->errors, true));
		return $service;
	}

	/**
	 * Создает ОС; domain_id может быть null (легаси-данные, в обход required-валидатора)
	 */
	private function makeComp(?int $domain_id): Comps
	{
		$comp = new Comps();
		$comp->setAttributes([
			'name' => 'absorbtsthost',
			'os' => 'Windows 10 Pro',
			'domain_id' => $domain_id,
		], false);
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		return $comp;
	}

	/**
	 * Привязывает сервис к ОС через junction comps_in_services (как select2 в форме)
	 */
	private function linkService(Comps $comp, Services $service): void
	{
		$comp->services_ids = array_merge($comp->services_ids, [$service->id]);
		$this->assertTrue($comp->save(false), print_r($comp->errors, true));
		$comp->refresh();
	}

	/**
	 * ОС без домена с сервисом поглощает клона с доменом (и без сервиса).
	 * Ожидание: клон удален, домен перенят (absorb=ifEmpty), сервис остался.
	 */
	public function testAbsorbCloneKeepsServiceAndGainsDomain()
	{
		$domain = $this->makeDomain();
		$service = $this->makeService();

		$survivor = $this->makeComp(null);
		$this->linkService($survivor, $service);
		$this->assertCount(1, $survivor->services, 'сервис должен быть привязан до поглощения');

		$clone = $this->makeComp($domain->id);

		//поглощаем клона (то, что делает CompsController::actionAbsorb)
		$survivor->absorbComp($clone);

		//клон удален
		$this->assertNull(Comps::findOne($clone->id), 'клон должен быть удален');

		//домен перенят у клона (absorb=ifEmpty на пустом domain_id)
		$survivorDb = Comps::findOne($survivor->id);
		$this->assertNotNull($survivorDb, 'выживший должен остаться в БД');
		$this->assertEquals($domain->id, $survivorDb->domain_id, 'домен клона должен перейти к выжившему');

		//сервис не должен пропасть
		$serviceIds = array_map(fn($s) => $s->id, $survivorDb->services);
		$this->assertContains($service->id, $serviceIds, 'привязка сервиса не должна пропадать при поглощении');
	}

	/**
	 * Сервис привязан к КЛОНУ (в UI-карточке дубля сервисы не видны).
	 * Ожидание: при поглощении привязка сервиса переезжает на выжившего.
	 */
	public function testAbsorbMovesCloneServiceToSurvivor()
	{
		$domain = $this->makeDomain();
		$service = $this->makeService();

		$survivor = $this->makeComp(null);
		$clone = $this->makeComp($domain->id);
		$this->linkService($clone, $service);
		$this->assertCount(1, $clone->services, 'сервис должен быть привязан к клону до поглощения');

		$survivor->absorbComp($clone);

		$this->assertNull(Comps::findOne($clone->id), 'клон должен быть удален');
		$survivorDb = Comps::findOne($survivor->id);
		$this->assertEquals($domain->id, $survivorDb->domain_id, 'домен клона должен перейти к выжившему');

		$serviceIds = array_map(fn($s) => $s->id, $survivorDb->services);
		$this->assertContains($service->id, $serviceIds, 'сервис клона должен переехать на выжившего');
	}

	/**
	 * Сервис привязан к ОБЕИМ записям (скрипт мог привязать один сервис к обоим клонам).
	 * Ожидание: после поглощения привязка сохраняется у выжившего (без дублей и потерь).
	 */
	public function testAbsorbWithServiceOnBothKeepsLink()
	{
		$domain = $this->makeDomain();
		$service = $this->makeService();

		$survivor = $this->makeComp(null);
		$clone = $this->makeComp($domain->id);
		$this->linkService($survivor, $service);
		$this->linkService($clone, $service);

		$survivor->absorbComp($clone);

		$this->assertNull(Comps::findOne($clone->id), 'клон должен быть удален');
		$survivorDb = Comps::findOne($survivor->id);
		$this->assertEquals($domain->id, $survivorDb->domain_id, 'домен клона должен перейти к выжившему');

		$serviceIds = array_map(fn($s) => $s->id, $survivorDb->services);
		$this->assertContains($service->id, $serviceIds, 'привязка сервиса не должна пропадать при поглощении');
	}

	/**
	 * Легаси-данные, не проходящие ТЕКУЩУЮ валидацию (выживший с пустой os,
	 * сервис с пустым description). Все save() внутри absorb игнорируют результат,
	 * а клон удаляется ДО сохранения выжившего — при любом падении валидации
	 * данные молча теряются: домен не переезжает, привязка сервиса виснет
	 * на удаленном клоне (FK на comps_in_services нет — строка сиротеет).
	 */
	public function testAbsorbWithLegacyInvalidDataMustNotLoseData()
	{
		$domain = $this->makeDomain();

		//легаси-сервис: не проходит required('description')
		$service = new Services();
		$service->setAttributes([
			'name' => 'absorb-legacy-service',
			'description' => '',
			'is_end_user' => 0,
		], false);
		$this->assertTrue($service->save(false), print_r($service->errors, true));
		$this->assertFalse($service->validate(), 'сервис задуман невалидным (легаси)');

		//легаси-выживший: не проходит required('os')
		$survivor = new Comps();
		$survivor->setAttributes([
			'name' => 'absorbtsthost',
			'os' => '',
			'domain_id' => null,
		], false);
		$this->assertTrue($survivor->save(false), print_r($survivor->errors, true));

		$clone = $this->makeComp($domain->id);
		$this->linkService($clone, $service);

		$survivor->absorbComp($clone);

		$this->assertNull(Comps::findOne($clone->id), 'клон должен быть удален');
		$survivorDb = Comps::findOne($survivor->id);
		$this->assertEquals($domain->id, $survivorDb->domain_id, 'домен клона должен перейти к выжившему');

		$serviceIds = array_map(fn($s) => $s->id, $survivorDb->services);
		$this->assertContains($service->id, $serviceIds, 'сервис клона должен переехать на выжившего, а не потеряться');
	}
}
