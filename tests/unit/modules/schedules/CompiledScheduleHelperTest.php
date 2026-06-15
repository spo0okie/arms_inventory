<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\compile\CompiledScheduleHelper;
use Codeception\Test\Unit;

/**
 * Тесты для CompiledScheduleHelper (PHP-порт ScheduleRuntime).
 *
 * Зеркалят ключевые кейсы из modules/schedules/compile/lib/js/demo.test.js,
 * чтобы гарантировать одинаковое поведение JS и PHP рантаймов.
 */
class CompiledScheduleHelperTest extends Unit
{
	/**
	 * Эталонное расписание по мотивам JS-теста.
	 */
	private static function sampleSchedule(): array
	{
		return [
			'tz' => 'UTC',
			'main' => [
				'name' => 'Офис',
				'start' => '2024-01-01',
				'start_tsm' => 28401120,
				'end' => null,
				'end_tsm' => null,
				'default' => [
					'schedule' => '08:00-17:00',
					'intervals' => [[480, 1020, []]],
				],
				'weekdays' => [
					'1' => ['schedule' => '08:00-17:30', 'intervals' => [[480, 1050, []]]],
					'5' => ['schedule' => '08:00-16:00', 'intervals' => [[480, 960, ['user' => 'pupkin']]]],
					'6' => ['schedule' => '-', 'intervals' => []],
					'7' => ['schedule' => '-', 'intervals' => []],
				],
				'dates' => [
					'28401120' => ['date_tsm' => 28401120, 'schedule' => '-', 'intervals' => []],
					'28402560' => ['date_tsm' => 28402560, 'schedule' => '10:00-15:00', 'intervals' => [[600, 900, []]]],
				],
				'periods' => [
					['start_tsm' => 28414680, 'end_tsm' => 28418139, 'is_work' => true],
					['start_tsm' => 28446430, 'end_tsm' => 28448047, 'is_work' => false],
				],
			],
			'overrides' => [
				[
					'name' => 'Лето 2024',
					'start_tsm' => 28620000,
					'end_tsm' => 28751040,
					'default' => ['schedule' => '09:00-18:00', 'intervals' => [[540, 1080, []]]],
					'weekdays' => [], 'dates' => [], 'periods' => [],
				],
			],
		];
	}

	// ---------------------------------------------------------------- utils

	public function testStrToTsm(): void
	{
		$this->assertSame(28401120, CompiledScheduleHelper::strToTsm('2024-01-01'));
		$this->assertSame(28401750, CompiledScheduleHelper::strToTsm('2024-01-01 10:30'));
		$this->assertNull(CompiledScheduleHelper::strToTsm(null));
		$this->assertNull(CompiledScheduleHelper::strToTsm(''));
	}

	public function testTsmToStr(): void
	{
		$this->assertStringStartsWith('2024-01-01', CompiledScheduleHelper::tsmToStr(28401120));
		$this->assertNull(CompiledScheduleHelper::tsmToStr(null));
	}

	public function testTsmToDateTsm(): void
	{
		$this->assertSame(28401120, CompiledScheduleHelper::tsmToDateTsm(28401750));
		$this->assertNull(CompiledScheduleHelper::tsmToDateTsm(null));
	}

	public function testDayOfWeek(): void
	{
		// 2024-01-01 (понедельник)
		$this->assertSame(1, CompiledScheduleHelper::dayOfWeek(28401120));
		// 2024-01-07 (воскресенье)
		$this->assertSame(7, CompiledScheduleHelper::dayOfWeek(28409760));
	}

	public function testInBounds(): void
	{
		$bounds = ['start_tsm' => 28401120, 'end_tsm' => 28429200];
		$this->assertTrue(CompiledScheduleHelper::inBounds(28416600, $bounds));
		$this->assertTrue(CompiledScheduleHelper::inBounds(28401120, $bounds)); // start включено
		$this->assertFalse(CompiledScheduleHelper::inBounds(28429200, $bounds)); // end исключено
		$this->assertFalse(CompiledScheduleHelper::inBounds(null, $bounds));
		$this->assertFalse(CompiledScheduleHelper::inBounds(28401120, null));
	}

	// ---------------------------------------------------------------- intervals

	public function testIntervalsContains(): void
	{
		// 2024-01-08 (пн) 10:00 = 28401120 + 7*1440 + 600 = 28411800+600
		$intervals = [[480, 1020, ['duty' => 'Иванов']]];
		$tsm = CompiledScheduleHelper::strToTsm('2024-01-08 10:00');
		$found = CompiledScheduleHelper::intervalsContains($intervals, $tsm);
		$this->assertNotNull($found);
		$this->assertSame(['duty' => 'Иванов'], $found[2]);

		// вне интервала
		$this->assertNull(CompiledScheduleHelper::intervalsContains($intervals, CompiledScheduleHelper::strToTsm('2024-01-08 07:00')));
	}

	public function testIntervalsSubtractAndAdd(): void
	{
		$res = CompiledScheduleHelper::intervalsSubtract([[480, 1020, []]], [600, 900, []]);
		$this->assertSame([[480, 600, []], [900, 1020, []]], $res);

		$res = CompiledScheduleHelper::intervalsAdd([[480, 1020, []]], [600, 900, []]);
		$this->assertSame([[480, 600, []], [900, 1020, []], [600, 900, []]], $res);
	}

	// ---------------------------------------------------------------- public API

	public function testIsWorkDay(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		$this->assertTrue($rt->isWorkDay('2024-01-08'), 'понедельник');
		$this->assertFalse($rt->isWorkDay('2024-01-06'), 'суббота');
		$this->assertTrue($rt->isWorkDay('2024-01-02'), 'дата-исключение с графиком');
		$this->assertFalse($rt->isWorkDay('2024-01-01'), 'дата-исключение выходной');
	}

	public function testIsWorkTime(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		$this->assertTrue($rt->isWorkTime('2024-01-08 10:00'));
		$this->assertFalse($rt->isWorkTime('2024-01-08 18:00'));
		$this->assertFalse($rt->isWorkTime('2024-01-08 07:00'));
		$this->assertTrue($rt->isWorkTime('2024-01-08 08:00'), 'левая граница');
	}

	public function testGetMeta(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		// пятница 2024-01-05 с duty
		$meta = $rt->getMeta('2024-01-05 10:00');
		$this->assertSame(['user' => 'pupkin'], $meta);

		$this->assertNull($rt->getMeta('2024-01-08 18:00'));
	}

	public function testFindOverride(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		// 2024-07-01 попадает в override
		$ov = $rt->findOverride(CompiledScheduleHelper::strToTsm('2024-07-01 10:00'));
		$this->assertSame('Лето 2024', $ov['name']);

		// 2024-01-15 — fallback в main
		$target = $rt->findOverride(CompiledScheduleHelper::strToTsm('2024-01-15 10:00'));
		$this->assertSame('Офис', $target['name']);
	}

	public function testNextOverride(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		$this->assertNotNull($rt->nextOverride(CompiledScheduleHelper::strToTsm('2024-01-01 10:00')));
		$this->assertNull($rt->nextOverride(CompiledScheduleHelper::strToTsm('2025-01-01 10:00')));
	}

	public function testFindPeriod(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		$p = $rt->findPeriod(CompiledScheduleHelper::strToTsm('2024-01-11 12:00'), true);
		$this->assertNotNull($p);
		$this->assertTrue($p['is_work']);
	}

	public function testNextPeriodStrictEnd(): void
	{
		$rt = new CompiledScheduleHelper([
			'main' => ['start_tsm' => 0, 'end_tsm' => null, 'periods' => [
				['start_tsm' => 28414680, 'end_tsm' => 28418139, 'is_work' => true],
			]],
			'overrides' => [],
		]);
		$this->assertNotNull($rt->nextPeriod(28415000, true));
		// end_tsm == pos → уже закончился
		$this->assertNull($rt->nextPeriod(28418139, true));
	}

	public function testNextWorkingDateTimeCurrentTime(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		$this->assertSame('2024-01-08 10:00', $rt->nextWorkingDateTime('2024-01-08 10:00'));
	}

	public function testNextWorkingDateTimeAcrossWeekend(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		// 2024-01-05 (пт) 20:00 → 2024-01-08 (пн) 08:00
		$this->assertSame('2024-01-08 08:00', $rt->nextWorkingDateTime('2024-01-05 20:00'));
	}

	public function testNextWorkingDateTimeBeforeStart(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		// До начала расписания
		$result = $rt->nextWorkingDateTime('2020-01-01 05:00');
		// 2024-01-01 — понедельник, но это date-исключение "-", поэтому найдётся следующая рабочая дата
		$this->assertIsString($result);
	}

	public function testGetDatePeriodsBoundary(): void
	{
		$rt = new CompiledScheduleHelper([
			'main' => [
				'start_tsm' => 0, 'end_tsm' => null,
				'periods' => [
					['start_tsm' => 28414080, 'end_tsm' => 28415520, 'is_work' => true],
				],
			],
			'overrides' => [],
		]);
		// период заканчивается ровно в начале дня → не пересекает
		$this->assertSame([], $rt->getDatePeriods(28415520));
	}

	public function testConstructorAcceptsJsonString(): void
	{
		$json = json_encode(self::sampleSchedule());
		$rt = new CompiledScheduleHelper($json);
		$this->assertTrue($rt->isWorkDay('2024-01-08'));
	}

	public function testNextWorkingMeta(): void
	{
		$rt = new CompiledScheduleHelper(self::sampleSchedule());
		// пятница 10:00 — meta="pupkin"
		$meta = $rt->nextWorkingMeta('2024-01-05 10:00');
		$this->assertSame(['user' => 'pupkin'], $meta);
	}

	/**
	 * Дни-исключения и периоды main имеют приоритет над перекрытием.
	 */
	public function testMainDateExceptionAndPeriodOverrideTheOverride(): void
	{
		$d1 = CompiledScheduleHelper::strToTsm('2024-07-01'); // внутри окна override
		$d2 = CompiledScheduleHelper::strToTsm('2024-07-02');
		$d3 = CompiledScheduleHelper::strToTsm('2024-07-03');
		$rt = new CompiledScheduleHelper([
			'tz' => 'UTC',
			'main' => [
				'name' => 'Main',
				'start_tsm' => CompiledScheduleHelper::strToTsm('2024-01-01'),
				'end_tsm' => null,
				'default' => ['schedule' => '08:00-17:00', 'intervals' => [[480, 1020, []]]],
				'weekdays' => [],
				'dates' => [
					(string)$d1 => ['date_tsm' => $d1, 'schedule' => '-', 'intervals' => []],
					(string)$d2 => ['date_tsm' => $d2, 'schedule' => '10:00-12:00', 'intervals' => [[600, 720, []]]],
				],
				'periods' => [
					// нерабочий период main внутри окна override: 2024-07-03 10:00-12:00
					['start_tsm' => $d3 + 600, 'end_tsm' => $d3 + 720, 'is_work' => false],
				],
			],
			'overrides' => [
				[
					'name' => 'Лето',
					'start_tsm' => CompiledScheduleHelper::strToTsm('2024-06-01'),
					'end_tsm' => CompiledScheduleHelper::strToTsm('2024-09-01'),
					'default' => ['schedule' => '09:00-18:00', 'intervals' => [[540, 1080, []]]],
					'weekdays' => [],
				],
			],
		]);

		// дата-исключение "-" перебивает недельный график override → выходной
		$this->assertFalse($rt->isWorkDay('2024-07-01'), 'дата-исключение-выходной перебивает override');
		// дата-исключение с графиком перебивает override (09:00-18:00 → 10:00-12:00)
		$this->assertSame([[600, 720, []]], $rt->getDateIntervals($d2));
		// нерабочий период main вычитается поверх графика override
		$this->assertSame([[540, 600, []], [720, 1080, []]], $rt->getDateIntervals($d3));
		// день в окне override без исключений/периодов → график override
		$this->assertSame([[540, 1080, []]], $rt->getDateIntervals(CompiledScheduleHelper::strToTsm('2024-07-04')));
		// nextWorkingDateTime: со дня-выходного-исключения внутри override → следующая рабочая дата
		$this->assertSame('2024-07-02 10:00', $rt->nextWorkingDateTime('2024-07-01 00:00'));
	}
}
