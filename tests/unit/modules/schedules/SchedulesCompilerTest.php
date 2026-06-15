<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\compile\SchedulesCompiler;
use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Тесты компилятора расписаний (modules/schedules/compile/compile.md).
 */
class SchedulesCompilerTest extends Unit
{
	/** @var int[] */
	private $createdScheduleIds = [];

	protected function _after()
	{
		if ($this->createdScheduleIds) {
			SchedulesEntries::deleteAll(['schedule_id' => $this->createdScheduleIds]);
			Schedules::deleteAll(['id' => $this->createdScheduleIds]);
		}
	}

	private function createSchedule(array $attrs = []): Schedules
	{
		$model = new Schedules(array_merge([
			'name' => 'Compiler test schedule',
			'description' => '',
		], $attrs));
		$this->assertTrue($model->save(false));
		$this->createdScheduleIds[] = $model->id;
		return $model;
	}

	private function addEntry(Schedules $schedule, array $attrs): SchedulesEntries
	{
		$model = new SchedulesEntries(array_merge([
			'schedule_id' => $schedule->id,
		], $attrs));
		$this->assertTrue($model->save(false));
		return $model;
	}

	// ------------------------------------------------------------------ parseSchedule

	public function testParseSimpleSchedule(): void
	{
		$result = SchedulesCompiler::parseSchedule('08:00-17:00');
		$this->assertCount(1, $result);
		$this->assertSame(480, $result[0][0]);
		$this->assertSame(1020, $result[0][1]);
		$this->assertInstanceOf(\stdClass::class, $result[0][2]);
		$this->assertEquals([], (array)$result[0][2]);
	}

	public function testParseMultipleIntervals(): void
	{
		$result = SchedulesCompiler::parseSchedule('08:00-12:00,13:00-17:00');
		$this->assertCount(2, $result);
		$this->assertSame([480, 720], [$result[0][0], $result[0][1]]);
		$this->assertSame([780, 1020], [$result[1][0], $result[1][1]]);
	}

	public function testParseDashReturnsEmpty(): void
	{
		$this->assertSame([], SchedulesCompiler::parseSchedule('-'));
	}

	public function testParseEmptyReturnsEmpty(): void
	{
		$this->assertSame([], SchedulesCompiler::parseSchedule(''));
		$this->assertSame([], SchedulesCompiler::parseSchedule(null));
	}

	public function testParseSchedulePreservesMeta(): void
	{
		$result = SchedulesCompiler::parseSchedule('10:00-15:00{"user":"pupkin"}');
		$this->assertCount(1, $result);
		$this->assertSame(['user' => 'pupkin'], (array)$result[0][2]);
	}

	public function testParseScheduleSortsByStart(): void
	{
		$result = SchedulesCompiler::parseSchedule('13:00-17:00,08:00-12:00');
		$this->assertSame(480, $result[0][0], 'Первый интервал должен быть 08:00');
		$this->assertSame(780, $result[1][0]);
	}

	// ------------------------------------------------------------------ strToTsm

	public function testStrToTsmDate(): void
	{
		// 2024-01-01 00:00 UTC = 28401120 минут от epoch
		$this->assertSame(28401120, SchedulesCompiler::strToTsm('2024-01-01'));
	}

	public function testStrToTsmDateTime(): void
	{
		// 2024-01-01 10:30 UTC = 28401120 + 630 = 28401750
		$this->assertSame(28401750, SchedulesCompiler::strToTsm('2024-01-01 10:30'));
	}

	public function testStrToTsmHandlesNullAndEmpty(): void
	{
		$this->assertNull(SchedulesCompiler::strToTsm(null));
		$this->assertNull(SchedulesCompiler::strToTsm(''));
		$this->assertNull(SchedulesCompiler::strToTsm('not-a-date'));
	}

	// ------------------------------------------------------------------ compile

	public function testCompileEmptyScheduleProducesBaseStructure(): void
	{
		$schedule = $this->createSchedule(['name' => 'Empty']);
		$compiled = SchedulesCompiler::compile($schedule);

		$this->assertArrayHasKey('main', $compiled);
		$this->assertArrayHasKey('overrides', $compiled);
		$this->assertSame('Empty', $compiled['main']['name']);
		$this->assertNull($compiled['main']['start_tsm']);
		$this->assertNull($compiled['main']['end_tsm']);
		$this->assertNull($compiled['main']['default']);
		$this->assertSame([], $compiled['main']['periods']);
		$this->assertSame([], $compiled['overrides']);
	}

	public function testCompileWithWeekdaysAndDefault(): void
	{
		$schedule = $this->createSchedule([
			'name' => 'Office',
			'start_date' => '2024-01-01',
		]);
		$this->addEntry($schedule, ['date' => 'def', 'schedule' => '08:00-17:00']);
		$this->addEntry($schedule, ['date' => '1', 'schedule' => '08:00-17:30']);
		$this->addEntry($schedule, ['date' => '6', 'schedule' => '-']);
		$this->addEntry($schedule, ['date' => '7', 'schedule' => '-']);

		$compiled = SchedulesCompiler::compile($schedule);
		$main = $compiled['main'];

		$this->assertNotNull($main['default']);
		$this->assertCount(1, $main['default']['intervals']);
		$this->assertSame([480, 1020], [$main['default']['intervals'][0][0], $main['default']['intervals'][0][1]]);

		$weekdays = $main['weekdays'];
		$this->assertArrayHasKey('1', $weekdays);
		$this->assertArrayHasKey('6', $weekdays);
		$this->assertArrayHasKey('7', $weekdays);
		$this->assertSame([480, 1050], [$weekdays['1']['intervals'][0][0], $weekdays['1']['intervals'][0][1]]);
		$this->assertSame([], $weekdays['6']['intervals']);

		$this->assertSame(28401120, $main['start_tsm']);
	}

	public function testCompileWithSpecificDate(): void
	{
		$schedule = $this->createSchedule(['name' => 'WithDate', 'start_date' => '2024-01-01']);
		$this->addEntry($schedule, [
			'date'     => '2024-01-02',
			'schedule' => '10:00-15:00',
		]);

		$compiled = SchedulesCompiler::compile($schedule);
		$dates = $compiled['main']['dates'];

		// 2024-01-02 00:00 UTC = 28402560
		$this->assertArrayHasKey('28402560', $dates);
		$this->assertSame(28402560, $dates['28402560']['date_tsm']);
		$this->assertSame([600, 900], [$dates['28402560']['intervals'][0][0], $dates['28402560']['intervals'][0][1]]);
	}

	public function testCompileWithPeriods(): void
	{
		$schedule = $this->createSchedule(['name' => 'WithPeriods']);
		$this->addEntry($schedule, [
			'date'      => '2024-01-10 10:00:00',
			'date_end'  => '2024-01-12 22:59:00',
			'is_period' => 1,
			'is_work'   => 1,
			'comment'   => 'Работали непрерывно',
		]);
		$this->addEntry($schedule, [
			'date'      => '2024-02-01 15:10:00',
			'date_end'  => '2024-02-02 18:17:00',
			'is_period' => 1,
			'is_work'   => 0,
			'comment'   => 'Авария',
		]);

		$compiled = SchedulesCompiler::compile($schedule);
		$periods = $compiled['main']['periods'];

		$this->assertCount(2, $periods);
		$this->assertLessThan($periods[1]['start_tsm'], $periods[0]['start_tsm'], 'Periods отсортированы по start_tsm');
		$this->assertTrue($periods[0]['is_work']);
		$this->assertFalse($periods[1]['is_work']);
	}

	public function testCompileWithOverrides(): void
	{
		$parent = $this->createSchedule(['name' => 'Parent', 'start_date' => '2024-01-01']);
		$override = $this->createSchedule([
			'name'        => 'Summer 2024',
			'parent_id'   => $parent->id,
			'override_id' => $parent->id,
			'start_date'  => '2024-06-01',
			'end_date'    => '2024-08-31',
		]);
		$this->addEntry($override, ['date' => 'def', 'schedule' => '09:00-18:00']);

		$parent->refresh();
		$compiled = SchedulesCompiler::compile($parent);

		$this->assertCount(1, $compiled['overrides']);
		$ov = $compiled['overrides'][0];
		$this->assertSame('Summer 2024', $ov['name']);
		$this->assertSame(28620000, $ov['start_tsm']); // 2024-06-01 00:00 UTC
		// end_tsm эксклюзивен: дата без времени расширяется до начала следующего дня
		// (endStrToTsm +1440), чтобы весь end_date входил в окно перекрытия.
		$this->assertSame(28752480, $ov['end_tsm']);  // 2024-09-01 00:00 UTC
		// periods не включаются в override
		$this->assertArrayNotHasKey('periods', $ov);
	}

	// ------------------------------------------------------------------ parent_id inheritance

	public function testCompileInheritsDefaultFromParent(): void
	{
		$parent = $this->createSchedule(['name' => 'Base']);
		$this->addEntry($parent, ['date' => 'def', 'schedule' => '08:00-17:00']);

		$child = $this->createSchedule(['name' => 'Child', 'parent_id' => $parent->id]);

		$compiled = SchedulesCompiler::compile($child);
		$this->assertNotNull($compiled['main']['default'], 'Default должен наследоваться от parent');
		$this->assertSame('08:00-17:00', $compiled['main']['default']['schedule']);
	}

	public function testCompileChildWeekdayOverridesParent(): void
	{
		$parent = $this->createSchedule(['name' => 'Base']);
		$this->addEntry($parent, ['date' => '1', 'schedule' => '08:00-17:00']);

		$child = $this->createSchedule(['name' => 'Child', 'parent_id' => $parent->id]);
		$this->addEntry($child, ['date' => '1', 'schedule' => '09:00-18:00']);

		$compiled = SchedulesCompiler::compile($child);
		$weekdays = $compiled['main']['weekdays'];
		$this->assertSame('09:00-18:00', $weekdays['1']['schedule'], 'Запись ребёнка должна перекрывать родителя');
	}

	public function testCompileInheritsWeekdaysWhenChildHasNone(): void
	{
		$parent = $this->createSchedule(['name' => 'Base']);
		$this->addEntry($parent, ['date' => '1', 'schedule' => '08:00-17:00']);
		$this->addEntry($parent, ['date' => '6', 'schedule' => '-']);

		$child = $this->createSchedule(['name' => 'Child', 'parent_id' => $parent->id]);

		$compiled = SchedulesCompiler::compile($child);
		$weekdays = $compiled['main']['weekdays'];
		$this->assertArrayHasKey('1', $weekdays);
		$this->assertArrayHasKey('6', $weekdays);
	}

	public function testOverrideDoesNotInheritFromParent(): void
	{
		$parent = $this->createSchedule(['name' => 'Root']);
		$this->addEntry($parent, ['date' => 'def', 'schedule' => '08:00-17:00']);

		$override = $this->createSchedule([
			'name'        => 'Override',
			'parent_id'   => $parent->id,
			'override_id' => $parent->id,
			'start_date'  => '2024-06-01',
			'end_date'    => '2024-08-31',
		]);

		// Компилируем сам override как main — он не должен унаследовать def от parent
		$compiled = SchedulesCompiler::compile($override);
		$this->assertNull($compiled['main']['default'], 'Override не наследует default');
	}

	public function testCompiledJsonIsSerializable(): void
	{
		$schedule = $this->createSchedule(['name' => 'Serialize', 'start_date' => '2024-01-01']);
		$this->addEntry($schedule, ['date' => 'def', 'schedule' => '08:00-17:00']);
		$compiled = SchedulesCompiler::compile($schedule);
		$json = json_encode($compiled, JSON_UNESCAPED_UNICODE);
		$this->assertIsString($json);
		$decoded = json_decode($json, true);
		$this->assertSame('Serialize', $decoded['main']['name']);
	}
}
