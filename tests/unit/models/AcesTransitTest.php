<?php

namespace tests\unit\models;

use app\models\Aces;
use Codeception\Test\Unit;
use Yii;

/**
 * Транзит межсервисных связей (plans/access-chains.md, итерация 3): указатель
 * «следующий хоп» между записями доступа, сторож циклов, сборка маршрутов.
 *
 * Опора на демо-данные (tests/_data/demo-seed/12-transit.sql):
 *   ACE 9200: Инвентаризация (24) → прокси (сервис 19), HTTPS TCP 44344
 *   ACE 9201: Сайт taburetka (22) → прокси (сервис 19), HTTPS TCP 45345
 *   ACE 9202: прокси (19) → Кластер 1С (18), HTTPS TCP 443
 *   рёбра: 9200→9202, 9201→9202
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class AcesTransitTest extends Unit
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
		Aces::resetTransitCache();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		Aces::resetTransitCache();
	}

	private function edges(): array
	{
		return Yii::$app->db->createCommand(
			'SELECT CONCAT(aces_id,">",next_aces_id) FROM aces_next_aces ORDER BY aces_id,next_aces_id'
		)->queryColumn();
	}

	// --- маршруты -----------------------------------------------------------

	public function testRoutesAreBuiltThroughBothDirections()
	{
		$this->assertSame([[9200, 9202]], Aces::findOne(9200)->routeIds, 'первый хоп: маршрут вниз');
		$this->assertSame([[9200, 9202], [9201, 9202]], Aces::findOne(9202)->routeIds,
			'последний хоп: оба входящих маршрута сходятся сюда');
		$this->assertTrue(Aces::findOne(9202)->hasTransit);

		$plain = Aces::findOne(9001);
		$this->assertFalse($plain->hasTransit, 'обычный доступ — один хоп');
		$this->assertSame([], $plain->routeIds);
	}

	public function testRoutesOfLoadsHopsInOrder()
	{
		$ace = Aces::findOne(9202);
		$routes = Aces::routesOf([$ace])[9202];
		$this->assertCount(2, $routes);
		$this->assertSame([9200, 9202], array_map(static fn(Aces $hop) => (int)$hop->id, $routes[0]));
		$this->assertSame(
			"Инвентаризация → Контроль доступа в интернет → Кластер 1С\n"
			. "Сайт taburetka → Контроль доступа в интернет → Кластер 1С",
			$ace->transit
		);
	}

	public function testRelationsReadBothSidesOfSameTable()
	{
		$this->assertSame([9202], array_map('intval', Aces::findOne(9200)->next_aces_ids));
		$prev = array_map('intval', Aces::findOne(9202)->prev_aces_ids);
		sort($prev);
		$this->assertSame([9200, 9201], $prev);
	}

	// --- запись -------------------------------------------------------------

	public function testLinkCanBeSetFromEitherSide()
	{
		Yii::$app->db->createCommand()->delete('aces_next_aces')->execute();
		Aces::resetTransitCache();

		//с конечной стороны: «это соединение продолжает вот тот входящий хоп»
		$last = Aces::findOne(9202);
		$last->prev_aces_ids = [9201];
		$this->assertTrue($last->save(), print_r($last->errors, true));
		$this->assertSame(['9201>9202'], $this->edges());

		//с начальной стороны
		$first = Aces::findOne(9200);
		$first->next_aces_ids = [9202];
		$this->assertTrue($first->save(), print_r($first->errors, true));
		$this->assertSame(['9200>9202', '9201>9202'], $this->edges());

		$this->assertSame([[9200, 9202], [9201, 9202]], Aces::findOne(9202)->routeIds, 'кэш рёбер сброшен при записи');
	}

	public function testCycleGuard()
	{
		//прямая петля
		$self = Aces::findOne(9200);
		$self->next_aces_ids = [9200];
		$this->assertFalse($self->validate(['next_aces_ids']));

		//возврат в пройденный хоп: 9200→9202 уже есть, 9202→9200 замкнуло бы маршрут
		$last = Aces::findOne(9202);
		$last->next_aces_ids = [9200];
		$this->assertFalse($last->validate(['next_aces_ids']), 'цикл через следующий хоп');
		$this->assertArrayHasKey('next_aces_ids', $last->errors);

		//то же с обратной стороны
		$first = Aces::findOne(9200);
		$first->prev_aces_ids = [9202];
		$this->assertFalse($first->validate(['prev_aces_ids']), 'цикл через предыдущий хоп');

		//корректная ссылка проходит
		$ok = Aces::findOne(9201);
		$ok->next_aces_ids = [9202];
		$this->assertTrue($ok->validate(['next_aces_ids']), print_r($ok->errors, true));
	}

	public function testDeletingHopCleansEdges()
	{
		$this->assertNotFalse(Aces::findOne(9202)->delete());
		$this->assertSame([], $this->edges(), 'junction-строки удалённого хопа вычищены');
		$this->assertFalse(Aces::findOne(9200)->hasTransit);
	}

	// --- стыки и кандидаты ---------------------------------------------------

	public function testTransitJointIsSoftCheck()
	{
		$first = Aces::findOne(9200);
		$last = Aces::findOne(9202);
		$this->assertTrue(Aces::transitJoint($first, $last), 'ресурс первого хопа (прокси) — субъект второго');
		//в обратную сторону стык не сходится: ресурс 9202 — 1С, субъект 9200 — Инвентаризация
		$this->assertFalse(Aces::transitJoint($last, $first));
	}

	public function testHopCandidatesFollowTheIntermediary()
	{
		$next = Aces::findOne(9200)->nextCandidates();
		$this->assertArrayHasKey(9202, $next, 'исходящая запись прокси — кандидат в следующий хоп');
		$this->assertArrayNotHasKey(9200, $next, 'сама запись в кандидатах не предлагается');

		$prev = Aces::findOne(9202)->prevCandidates();
		$this->assertArrayHasKey(9200, $prev);
		$this->assertArrayHasKey(9201, $prev);
		$this->assertStringContainsString('Инвентаризация → Контроль доступа в интернет', $prev[9200]);
	}
}
