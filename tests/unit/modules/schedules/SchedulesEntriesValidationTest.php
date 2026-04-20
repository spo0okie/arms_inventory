<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Тесты валидаций SchedulesEntries по правилам из modules/schedules/compile/compile.md.
 *
 * Покрываем:
 * - уникальность weekday-entries (1..7, def)
 * - уникальность date-entries (конкретная дата)
 * - непересечение period-entries
 */
class SchedulesEntriesValidationTest extends Unit
{
	/** @var Schedules */
	private $owner;

	protected function _before()
	{
		$this->owner = new Schedules([
			'name' => 'Test schedule',
			'description' => 'validation fixture',
		]);
		$this->assertTrue($this->owner->save(false), 'Тестовое расписание должно сохраниться');
	}

	protected function _after()
	{
		if ($this->owner && $this->owner->id) {
			SchedulesEntries::deleteAll(['schedule_id' => $this->owner->id]);
			Schedules::deleteAll(['id' => $this->owner->id]);
		}
	}

	/**
	 * День недели (1..7) может быть записан один раз на расписание.
	 */
	public function testWeekdayUniquenessRejectsDuplicate(): void
	{
		$first = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '1',
			'schedule'    => '08:00-17:00',
			'is_period'   => 0,
		]);
		$this->assertTrue($first->save(), 'Первая запись на понедельник должна сохраниться');

		$duplicate = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '1',
			'schedule'    => '09:00-18:00',
			'is_period'   => 0,
		]);
		$this->assertFalse($duplicate->validate(), 'Дубликат на тот же день недели должен быть отклонён');
		$this->assertArrayHasKey('date', $duplicate->errors, 'Ошибка должна быть на поле date');
	}

	/**
	 * Ключ "def" тоже уникален — проверяется как weekday-key.
	 */
	public function testDefaultWeekdayUniquenessRejectsDuplicate(): void
	{
		$first = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => 'def',
			'schedule'    => '08:00-17:00',
			'is_period'   => 0,
		]);
		$this->assertTrue($first->save());

		$duplicate = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => 'def',
			'schedule'    => '09:00-18:00',
			'is_period'   => 0,
		]);
		$this->assertFalse($duplicate->validate(), 'Дубликат на def должен быть отклонён');
		$this->assertArrayHasKey('date', $duplicate->errors);
	}

	/**
	 * Разные дни недели не конфликтуют.
	 */
	public function testDifferentWeekdaysAreAllowed(): void
	{
		$monday = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '1',
			'schedule'    => '08:00-17:00',
			'is_period'   => 0,
		]);
		$tuesday = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2',
			'schedule'    => '08:00-17:00',
			'is_period'   => 0,
		]);
		$this->assertTrue($monday->save());
		$this->assertTrue($tuesday->validate(), 'Запись на другой день недели должна быть валидной');
	}

	/**
	 * Повторная запись на ту же дату (Y-m-d) отклоняется.
	 */
	public function testDateUniquenessRejectsDuplicate(): void
	{
		$first = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-01-01',
			'schedule'    => '10:00-15:00',
			'is_period'   => 0,
		]);
		$this->assertTrue($first->save(), 'Первая запись на дату должна сохраниться');

		$duplicate = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-01-01',
			'schedule'    => '11:00-16:00',
			'is_period'   => 0,
		]);
		$this->assertFalse($duplicate->validate(), 'Дубликат на ту же дату должен быть отклонён');
		$this->assertArrayHasKey('date', $duplicate->errors);
	}

	/**
	 * Периоды не должны пересекаться внутри одного расписания.
	 */
	public function testPeriodIntersectionRejected(): void
	{
		$p1 = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-02-01',
			'date_end'    => '2024-02-10',
			'is_period'   => 1,
			'is_work'     => 1,
		]);
		$this->assertTrue($p1->save(), 'Первый период должен сохраниться');

		$p2 = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-02-05',
			'date_end'    => '2024-02-15',
			'is_period'   => 1,
			'is_work'     => 1,
		]);
		$this->assertFalse($p2->validate(), 'Пересекающийся период должен быть отклонён');
	}

	/**
	 * Неперекрывающиеся периоды допустимы.
	 */
	public function testNonOverlappingPeriodsAllowed(): void
	{
		$p1 = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-02-01',
			'date_end'    => '2024-02-10',
			'is_period'   => 1,
			'is_work'     => 1,
		]);
		$this->assertTrue($p1->save());

		$p2 = new SchedulesEntries([
			'schedule_id' => $this->owner->id,
			'date'        => '2024-03-01',
			'date_end'    => '2024-03-10',
			'is_period'   => 1,
			'is_work'     => 1,
		]);
		$this->assertTrue($p2->validate(), 'Не пересекающийся период должен пройти валидацию');
	}
}
