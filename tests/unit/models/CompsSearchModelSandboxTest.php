<?php

namespace tests\unit\models;

use app\controllers\CompsController;
use app\models\Comps;
use app\models\Domains;
use app\models\Sandboxes;
use app\models\Soft;
use Codeception\Test\Unit;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Тесты WYSIWYG-поиска ОС по имени с учётом песочниц (CompsController::searchModel).
 *
 * Продуктивная ОС и её клон в песочнице имеют одинаковые name и domain_id
 * (уникальный ключ domain_id+name+sandbox_id), различаясь только суффиксом
 * песочницы в отображаемом имени (renderName). Поиск по имени должен
 * резолвить имя так, как оно отображается:
 *  - имя без суффикса — продуктивная ОС;
 *  - имя с суффиксом песочницы — клон в этой песочнице (включая FQDN-форму,
 *    где суффикс стоит после домена);
 *  - легаси-фоллбек: имя, существующее только у клона, находит клона.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class CompsSearchModelSandboxTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var string суффикс тестовой песочницы */
	private $suffix;
	/** @var string netbios-имя пары продуктив+клон */
	private $base;
	/** @var Domains */
	private $domain;
	/** @var Sandboxes */
	private $sandbox;
	/** @var Comps */
	private $production;
	/** @var Comps */
	private $clone;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		Soft::$disable_rescan = true;

		$uid = substr(uniqid(), -8);
		$this->suffix = '-' . $uid;
		$this->base = 'sbxhost' . $uid;

		$this->domain = new Domains([
			'name' => 'SBX' . strtoupper($uid),
			'fqdn' => 'sbx' . $uid . '.local',
			'comment' => 'searchModel sandbox test',
		]);
		$this->assertTrue($this->domain->save(false));

		$this->sandbox = new Sandboxes([
			'name' => 'searchModel test ' . $uid,
			'suffix' => $this->suffix,
		]);
		$this->assertTrue($this->sandbox->save(false));

		$this->production = $this->makeComp($this->base, null);
		$this->clone = $this->makeComp($this->base, $this->sandbox->id);
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

	private function makeComp(string $name, ?int $sandbox_id): Comps
	{
		$comp = new Comps();
		$comp->name = $name;
		$comp->domain_id = $this->domain->id;
		$comp->sandbox_id = $sandbox_id;
		$this->assertTrue($comp->save(false));
		return $comp;
	}

	/**
	 * Имя без суффикса — продуктивная ОС, а не случайная из пары.
	 */
	public function testPlainNameFindsProduction()
	{
		$found = CompsController::searchModel($this->base);
		$this->assertEquals($this->production->id, $found->id,
			'Имя без суффикса должно находить ОС без песочницы');
	}

	/**
	 * Имя с суффиксом песочницы — клон в этой песочнице.
	 */
	public function testSuffixedNameFindsClone()
	{
		$found = CompsController::searchModel($this->base . $this->suffix);
		$this->assertEquals($this->clone->id, $found->id,
			'Имя с суффиксом должно находить клона в песочнице');
	}

	/**
	 * Регистр не влияет ни на имя, ни на суффикс.
	 */
	public function testSuffixedNameCaseInsensitive()
	{
		$found = CompsController::searchModel(strtoupper($this->base . $this->suffix));
		$this->assertEquals($this->clone->id, $found->id);
	}

	/**
	 * FQDN-форма: суффикс стоит после домена (как в renderName(true))
	 * и должен срезаться до разбора домена.
	 */
	public function testFqdnSuffixedFindsClone()
	{
		$found = CompsController::searchModel($this->base . '.' . $this->domain->fqdn . $this->suffix);
		$this->assertEquals($this->clone->id, $found->id,
			'FQDN с суффиксом должен находить клона');
	}

	/**
	 * FQDN-форма без суффикса — продуктивная ОС.
	 */
	public function testFqdnPlainFindsProduction()
	{
		$found = CompsController::searchModel($this->base . '.' . $this->domain->fqdn);
		$this->assertEquals($this->production->id, $found->id);
	}

	/**
	 * ОС, чьё хранимое имя буквально совпадает с введённым (включая «суффикс»),
	 * выигрывает у суффиксной интерпретации: её отображаемое имя и есть введённое.
	 */
	public function testLiteralNameBeatsSuffixInterpretation()
	{
		$literal = $this->makeComp($this->base . $this->suffix, null);

		$found = CompsController::searchModel($this->base . $this->suffix);
		$this->assertEquals($literal->id, $found->id,
			'Буквальное совпадение хранимого имени приоритетнее срезания суффикса');
	}

	/**
	 * Легаси-фоллбек: имя есть только у клона (продуктива нет) — находим клона,
	 * старые ссылки не ломаются.
	 */
	public function testCloneOnlyNameFallsBackToClone()
	{
		$cloneOnly = $this->makeComp($this->base . 'only', $this->sandbox->id);

		$found = CompsController::searchModel($this->base . 'only');
		$this->assertEquals($cloneOnly->id, $found->id,
			'Имя, существующее только у клона, должно находить клона');
	}

	/**
	 * Несуществующее имя — 404 как и раньше.
	 */
	public function testMissingNameThrows()
	{
		$this->expectException(NotFoundHttpException::class);
		CompsController::searchModel($this->base . 'missing');
	}

	/**
	 * Comps::findByAnyName (REST push/LicLinks/LoginJournal) — та же
	 * WYSIWYG-семантика: имя без суффикса это продуктив, а не случайная из пары.
	 */
	public function testFindByAnyNamePlainFindsProduction()
	{
		$found = Comps::findByAnyName($this->base . '.' . $this->domain->fqdn);
		$this->assertEquals($this->production->id, $found->id);
	}

	/**
	 * findByAnyName: имя с суффиксом песочницы — клон, включая FQDN-форму.
	 */
	public function testFindByAnyNameSuffixedFindsClone()
	{
		$found = Comps::findByAnyName($this->base . '.' . $this->domain->fqdn . $this->suffix);
		$this->assertEquals($this->clone->id, $found->id);

		$found = Comps::findByAnyName($this->domain->name . '\\' . $this->base . $this->suffix);
		$this->assertEquals($this->clone->id, $found->id);
	}

	/**
	 * findByAnyName: имя только у клона — легаси-фоллбек находит клона.
	 */
	public function testFindByAnyNameCloneOnlyFallsBack()
	{
		$cloneOnly = $this->makeComp($this->base . 'fbn', $this->sandbox->id);

		$found = Comps::findByAnyName($this->domain->name . '\\' . $this->base . 'fbn');
		$this->assertEquals($cloneOnly->id, $found->id);
	}

	/**
	 * findByAnyName: прежняя семантика ответов сохранена —
	 * false при битом формате имени, null если ничего не найдено.
	 */
	public function testFindByAnyNameLegacyReturnValues()
	{
		$this->assertFalse(Comps::findByAnyName('DOM\\comp\\extra'),
			'Битый формат имени должен вернуть false');
		$this->assertNull(Comps::findByAnyName($this->base . 'missing.' . $this->domain->fqdn),
			'Ненайденное имя должно вернуть null');
	}
}
