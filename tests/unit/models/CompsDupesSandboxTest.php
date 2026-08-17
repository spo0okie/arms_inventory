<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\Domains;
use app\models\Sandboxes;
use app\models\Soft;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты отбора дублей ОС ({@see Comps::dupeIds()} — список /comps/dupes,
 * {@see Comps::getDupes()} — блок подозрений в карточке ОС).
 *
 * Поиск дублей написан до появления песочниц и архивности и искал одинаковый
 * name по всей таблице. Двойником не является:
 *  - клон продуктива в песочнице: он намеренно носит то же имя (уникальный ключ
 *    domain_id+name+sandbox_id, отображаемое имя различается суффиксом
 *    песочницы) — это изоляция;
 *  - архивная запись: она хранится ради истории и в поиске двойников не
 *    участвует ни одной из сторон.
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

	private function makeComp(string $name, ?int $sandbox_id, ?int $domain_id = null, $archived = null): Comps
	{
		$comp = new Comps();
		$comp->name = $name;
		$comp->domain_id = $domain_id ?? $this->domain->id;
		$comp->sandbox_id = $sandbox_id;
		$comp->archived = $archived;
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

	/**
	 * Архивный тёзка не делает живую запись дублем и сам в список не попадает.
	 */
	public function testArchivedIsNotDupe()
	{
		$live = $this->makeComp($this->base, null);
		$archived = $this->makeComp($this->base, null, $this->otherDomain->id, 1);

		$ids = Comps::dupeIds();
		$this->assertNotContains($archived->id, $ids,
			'Архивная запись не должна попадать в список дублей');
		$this->assertNotContains($live->id, $ids,
			'Живая запись не дубль, если её единственный тёзка — архивный');
	}

	/**
	 * Две архивные записи с одним именем — тоже не дубли: архив не разбираем.
	 */
	public function testTwoArchivedAreNotDupes()
	{
		$first = $this->makeComp($this->base, null, null, 1);
		$second = $this->makeComp($this->base, null, $this->otherDomain->id, 1);

		$ids = Comps::dupeIds();
		$this->assertNotContains($first->id, $ids);
		$this->assertNotContains($second->id, $ids);
	}

	/**
	 * Карточка ОС: архивный тёзка не показывается в подозрениях,
	 * а у самой архивной записи блок подозрений пуст.
	 */
	public function testRelationIgnoresArchived()
	{
		$live = $this->makeComp($this->base, null);
		$archived = $this->makeComp($this->base, null, $this->otherDomain->id, 1);

		$this->assertCount(0, $live->dupes,
			'Архивный тёзка не должен показываться дублем в карточке живой ОС');
		$this->assertCount(0, $archived->dupes,
			'У архивной ОС подозрений на дубликаты нет');
	}

	/**
	 * Живые дубли остаются дублями и для архивного тёзки рядом:
	 * архивная запись просто не участвует в подсчёте.
	 */
	public function testArchivedDoesNotHideLiveDupes()
	{
		$first = $this->makeComp($this->base, null);
		$second = $this->makeComp($this->base, null, $this->otherDomain->id);
		$archived = $this->makeComp($this->base, null, null, 1);

		$ids = Comps::dupeIds();
		$this->assertContains($first->id, $ids);
		$this->assertContains($second->id, $ids);
		$this->assertNotContains($archived->id, $ids);

		$this->assertCount(1, $first->dupes, 'В карточке виден только живой дубль');
		$this->assertEquals($second->id, $first->dupes[0]->id);
	}
}
