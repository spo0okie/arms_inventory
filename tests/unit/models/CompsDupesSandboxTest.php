<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\Domains;
use app\models\Sandboxes;
use app\models\Soft;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты отбора дублей ОС с учётом песочниц ({@see Comps::dupeIds()}, список /comps/dupes).
 *
 * Поиск дублей написан до появления песочниц и искал одинаковый name по всей
 * таблице. Клон продуктива в песочнице намеренно носит то же имя (уникальный ключ
 * domain_id+name+sandbox_id, отображаемое имя различается суффиксом песочницы) —
 * это изоляция, а не двойник, и в списке дублей его быть не должно.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class CompsDupesSandboxTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var string netbios-имя пары продуктив+клон */
	private $base;
	/** @var Domains */
	private $domain;
	/** @var Domains второй домен: дубли ищутся без учёта домена */
	private $otherDomain;
	/** @var Sandboxes */
	private $sandbox;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		Soft::$disable_rescan = true;

		$uid = substr(uniqid(), -8);
		$this->base = 'dupehost' . $uid;

		$this->domain = new Domains([
			'name' => 'DUP' . strtoupper($uid),
			'fqdn' => 'dup' . $uid . '.local',
			'comment' => 'dupes sandbox test',
		]);
		$this->assertTrue($this->domain->save(false));

		$this->otherDomain = new Domains([
			'name' => 'DUX' . strtoupper($uid),
			'fqdn' => 'dux' . $uid . '.local',
			'comment' => 'dupes sandbox test',
		]);
		$this->assertTrue($this->otherDomain->save(false));

		$this->sandbox = new Sandboxes([
			'name' => 'dupes test ' . $uid,
			'suffix' => '-' . $uid,
		]);
		$this->assertTrue($this->sandbox->save(false));
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		//кэш справочника мог набрать откаченные записи
		Sandboxes::invalidateAllItemsCache();
		Soft::$disable_rescan = false;
	}

	private function makeComp(string $name, ?int $sandbox_id, ?int $domain_id = null): Comps
	{
		$comp = new Comps();
		$comp->name = $name;
		$comp->domain_id = $domain_id ?? $this->domain->id;
		$comp->sandbox_id = $sandbox_id;
		$this->assertTrue($comp->save(false));
		return $comp;
	}

	/**
	 * Продуктив и его клон в песочнице — не дубли: имена совпадают, окружения разные.
	 */
	public function testCloneIsNotDupeOfProduction()
	{
		$production = $this->makeComp($this->base, null);
		$clone = $this->makeComp($this->base, $this->sandbox->id);

		$ids = Comps::dupeIds();
		$this->assertNotContains($production->id, $ids,
			'Продуктив не должен считаться дублем своего клона в песочнице');
		$this->assertNotContains($clone->id, $ids,
			'Клон в песочнице изолирован — это не дубль продуктива');
	}

	/**
	 * Два клона с одним именем внутри одной песочницы — дубли:
	 * изоляция работает между окружениями, а не внутри одного.
	 */
	public function testDupesInsideSameSandboxAreFound()
	{
		$first = $this->makeComp($this->base, $this->sandbox->id);
		$second = $this->makeComp($this->base, $this->sandbox->id, $this->otherDomain->id);

		$ids = Comps::dupeIds();
		$this->assertContains($first->id, $ids);
		$this->assertContains($second->id, $ids,
			'Одинаковые имена внутри одной песочницы — по-прежнему дубли');
	}

	/**
	 * Дубли в продуктиве (sandbox_id IS NULL) находятся как и раньше:
	 * NULL-окружение — такая же группа, а не «каждый сам по себе».
	 */
	public function testDupesInProductionAreFound()
	{
		$first = $this->makeComp($this->base, null);
		$second = $this->makeComp($this->base, null, $this->otherDomain->id);

		$ids = Comps::dupeIds();
		$this->assertContains($first->id, $ids);
		$this->assertContains($second->id, $ids,
			'Дубли без песочницы должны находиться как и до появления песочниц');
	}

	/**
	 * Одиночная запись дублем не считается (контроль на отсутствие ложных срабатываний).
	 */
	public function testSingleCompIsNotDupe()
	{
		$single = $this->makeComp($this->base, null);

		$this->assertNotContains($single->id, Comps::dupeIds());
	}

	/**
	 * Релейшн отдельной ОС {@see Comps::getDupes()} и список /comps/dupes
	 * должны сходиться: клон не видит продуктив своим дублем.
	 */
	public function testRelationAgreesWithList()
	{
		$production = $this->makeComp($this->base, null);
		$clone = $this->makeComp($this->base, $this->sandbox->id);

		$this->assertCount(0, $production->dupes,
			'У продуктива не должно быть дублей: совпадает только клон в песочнице');
		$this->assertCount(0, $clone->dupes);
	}
}
