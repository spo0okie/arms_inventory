<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use Codeception\Test\Unit;

/**
 * Тесты валидаций overrides (перекрытий) расписаний.
 * Правила из modules/schedules/compile/compile.md:
 * - overrides одного родителя не должны пересекаться.
 */
class SchedulesOverrideValidationTest extends Unit
{
	/** @var Schedules */
	private $parent;
	/** @var int[] */
	private $createdIds = [];

	protected function _before()
	{
		$this->parent = new Schedules([
			'name' => 'Parent schedule',
			'description' => 'override validation fixture',
		]);
		$this->assertTrue($this->parent->save(false));
		$this->createdIds[] = $this->parent->id;
	}

	protected function _after()
	{
		if ($this->createdIds) {
			Schedules::deleteAll(['id' => $this->createdIds]);
		}
	}

	private function createOverride(string $start, ?string $end): Schedules
	{
		$model = new Schedules([
			'name' => 'Override '.$start.'-'.($end ?? 'inf'),
			'parent_id' => $this->parent->id,
			'override_id' => $this->parent->id,
			'start_date' => $start,
			'end_date' => $end,
		]);
		$model->scenario = Schedules::SCENARIO_OVERRIDE;
		return $model;
	}

	/**
	 * Два непересекающихся override по последовательным датам — валидно
	 * при условии, что end одного строго раньше start второго.
	 */
	public function testNonOverlappingOverridesAllowed(): void
	{
		$a = $this->createOverride('2024-01-01', '2024-03-31');
		$this->assertTrue($a->save(), 'Первый override должен сохраниться: '.implode('; ', $a->firstErrors));
		$this->createdIds[] = $a->id;

		$b = $this->createOverride('2024-04-01', '2024-06-30');
		$this->assertTrue($b->validate(), 'Непересекающийся override должен пройти валидацию: '.implode('; ', $b->firstErrors));
	}

	/**
	 * Явно пересекающиеся override должны отклоняться.
	 */
	public function testOverlappingOverridesRejected(): void
	{
		$a = $this->createOverride('2024-01-01', '2024-06-30');
		$this->assertTrue($a->save());
		$this->createdIds[] = $a->id;

		$b = $this->createOverride('2024-06-01', '2024-12-31');
		$this->assertFalse($b->validate(), 'Перекрывающийся override должен быть отклонён');
	}

	/**
	 * Override, полностью вложенный в другой, должен отклоняться.
	 */
	public function testNestedOverrideRejected(): void
	{
		$outer = $this->createOverride('2024-01-01', '2024-12-31');
		$this->assertTrue($outer->save());
		$this->createdIds[] = $outer->id;

		$inner = $this->createOverride('2024-06-01', '2024-06-30');
		$this->assertFalse($inner->validate(), 'Вложенный override должен быть отклонён');
	}

	/**
	 * Бесконечный override (end = null) перекрывает любой последующий.
	 */
	public function testUnboundedOverrideBlocksSubsequent(): void
	{
		$infinite = $this->createOverride('2024-01-01', null);
		$this->assertTrue($infinite->save());
		$this->createdIds[] = $infinite->id;

		$later = $this->createOverride('2025-01-01', '2025-03-31');
		$this->assertFalse($later->validate(), 'Override после бесконечного должен быть отклонён');
	}
}
