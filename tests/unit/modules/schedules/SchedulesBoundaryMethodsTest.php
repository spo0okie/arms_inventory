<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use Codeception\Test\Unit;

/**
 * Тесты для параметрических методов Schedules:
 *  - endsBeforeDate($date)
 *  - startsAfterDate($date)
 *  - matchDate($date)
 *
 * Раньше тесты лежали в `modules/schedules/tests/unit/SchedulesModelCalcFieldsTraitTest.php`
 * и работали с анонимным классом-заглушкой. После того как методы переехали из трейта
 * `SchedulesModelCalcFieldsTrait` в саму модель `Schedules` (см. соглашение в README модуля),
 * тесты переехали сюда — работают с реальной моделью без БД (in-memory).
 */
class SchedulesBoundaryMethodsTest extends Unit
{
	private function build(array $attrs = []): Schedules
	{
		// Не сохраняем в БД — для этих методов достаточно in-memory объекта со start_date/end_date.
		return new Schedules($attrs);
	}

	// -------------------------------------------------------------------------
	// endsBeforeDate
	// -------------------------------------------------------------------------

	public function testEndsBeforeDateFalseWhenNoEndDate(): void
	{
		$this->assertFalse($this->build(['end_date' => null])->endsBeforeDate('2024-06-01'));
	}

	public function testEndsBeforeDateTrueWhenEndBeforeDate(): void
	{
		$this->assertTrue($this->build(['end_date' => '2024-01-01'])->endsBeforeDate('2024-06-01'));
	}

	public function testEndsBeforeDateFalseWhenEndAfterDate(): void
	{
		$this->assertFalse($this->build(['end_date' => '2024-12-31'])->endsBeforeDate('2024-06-01'));
	}

	public function testEndsBeforeDateAcceptsUnixtime(): void
	{
		$this->assertTrue($this->build(['end_date' => '2024-01-01'])->endsBeforeDate(strtotime('2024-06-01')));
	}

	// -------------------------------------------------------------------------
	// startsAfterDate
	// -------------------------------------------------------------------------

	public function testStartsAfterDateFalseWhenNoStartDate(): void
	{
		$this->assertFalse($this->build(['start_date' => null])->startsAfterDate('2024-06-01'));
	}

	public function testStartsAfterDateTrueWhenStartAfterDate(): void
	{
		$this->assertTrue($this->build(['start_date' => '2024-12-01'])->startsAfterDate('2024-06-01'));
	}

	public function testStartsAfterDateFalseWhenStartBeforeDate(): void
	{
		$this->assertFalse($this->build(['start_date' => '2024-01-01'])->startsAfterDate('2024-06-01'));
	}

	// -------------------------------------------------------------------------
	// matchDate
	// -------------------------------------------------------------------------

	public function testMatchDateTrueWhenNoBoundaries(): void
	{
		$this->assertTrue($this->build(['start_date' => null, 'end_date' => null])->matchDate('2024-06-01'));
	}

	public function testMatchDateFalseWhenBeforeStart(): void
	{
		$this->assertFalse($this->build(['start_date' => '2024-07-01', 'end_date' => null])->matchDate('2024-06-01'));
	}

	public function testMatchDateFalseWhenAfterEnd(): void
	{
		$this->assertFalse($this->build(['start_date' => null, 'end_date' => '2024-05-01'])->matchDate('2024-06-01'));
	}

	public function testMatchDateTrueWhenInsideBoundaries(): void
	{
		$this->assertTrue($this->build([
			'start_date' => '2024-01-01',
			'end_date'   => '2024-12-31',
		])->matchDate('2024-06-01'));
	}
}
