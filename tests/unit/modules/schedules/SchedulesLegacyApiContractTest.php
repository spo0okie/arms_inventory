<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Contract-тесты для legacy-методов модели Schedules:
 *  - isWorkTime($date, $time)        : int 0|1
 *  - getStatus()                     : int 0|1
 *  - metaAtTime($date, $time)        : '{}' либо raw-строка вида '{user:pupkin}'
 *  - nextWorkingMeta($date, $time)   : '{}' либо raw-строка
 *
 * Эти тесты пишутся для **закрепления текущего поведения** перед заменой
 * реализаций на тонкие обёртки над CompiledScheduleHelper. Они должны быть
 * зелёными ДО рефакторинга и ДОЛЖНЫ оставаться зелёными ПОСЛЕ.
 *
 * По объёму и кейсам зеркалят CompiledScheduleHelperTest.
 */
class SchedulesLegacyApiContractTest extends Unit
{
	/** @var int[] */
	private $createdIds = [];

	protected function _after()
	{
		if ($this->createdIds) {
			SchedulesEntries::deleteAll(['schedule_id' => $this->createdIds]);
			Schedules::deleteAll(['id' => $this->createdIds]);
		}
	}

	private function createSchedule(array $attrs = []): Schedules
	{
		$model = new Schedules(array_merge(['name' => 'Contract'], $attrs));
		$this->assertTrue($model->save());
		$this->createdIds[] = $model->id;
		return $model;
	}

	private function addEntry(Schedules $schedule, array $attrs): SchedulesEntries
	{
		$entry = new SchedulesEntries(array_merge(['schedule_id' => $schedule->id], $attrs));
		$this->assertTrue($entry->save(), 'entry save: ' . json_encode($entry->errors));
		return $entry;
	}

	/**
	 * Стандартное эталонное расписание: пн-пт 08:00-17:00, сб/вс выходные,
	 * 2024-01-01 — выходной (дата-исключение), 2024-01-02 — 10:00-15:00 (дата-исключение).
	 */
	private function buildOfficeSchedule(): Schedules
	{
		$s = $this->createSchedule(['name' => 'Office', 'start_date' => '2024-01-01']);
		$this->addEntry($s, ['date' => 'def', 'schedule' => '08:00-17:00']);
		$this->addEntry($s, ['date' => '6', 'schedule' => '-']);
		$this->addEntry($s, ['date' => '7', 'schedule' => '-']);
		$this->addEntry($s, ['date' => '2024-01-01', 'schedule' => '-']);
		$this->addEntry($s, ['date' => '2024-01-02', 'schedule' => '10:00-15:00']);
		return $s;
	}

	// =========================================================================
	// isWorkTime
	// =========================================================================

	public function testIsWorkTimeMonday10WorkHours(): void
	{
		$s = $this->buildOfficeSchedule();
		// 2024-01-08 — понедельник
		$this->assertSame(1, $s->isWorkTime('2024-01-08', '10:00'));
	}

	public function testIsWorkTimeMonday07BeforeStart(): void
	{
		$s = $this->buildOfficeSchedule();
		$this->assertSame(0, $s->isWorkTime('2024-01-08', '07:00'));
	}

	public function testIsWorkTimeMonday18AfterEnd(): void
	{
		$s = $this->buildOfficeSchedule();
		$this->assertSame(0, $s->isWorkTime('2024-01-08', '18:00'));
	}

	public function testIsWorkTimeMonday17EndBoundaryInclusive(): void
	{
		$s = $this->buildOfficeSchedule();
		// LEGACY-фича: правая граница ВКЛЮЧЕНА (отличается от compiled-контракта [start, end)).
		// Обёртка должна сохранять это поведение для backward-compat.
		$this->assertSame(1, $s->isWorkTime('2024-01-08', '17:00'));
	}

	public function testIsWorkTimeMonday08StartBoundaryInclusive(): void
	{
		$s = $this->buildOfficeSchedule();
		// левая граница входит
		$this->assertSame(1, $s->isWorkTime('2024-01-08', '08:00'));
	}

	public function testIsWorkTimeSaturdayWeekend(): void
	{
		$s = $this->buildOfficeSchedule();
		// 2024-01-06 — суббота
		$this->assertSame(0, $s->isWorkTime('2024-01-06', '10:00'));
	}

	public function testIsWorkTimeDateExceptionHoliday(): void
	{
		$s = $this->buildOfficeSchedule();
		// 2024-01-01 — заданная дата-исключение «-»
		$this->assertSame(0, $s->isWorkTime('2024-01-01', '10:00'));
	}

	public function testIsWorkTimeDateExceptionShortDay(): void
	{
		$s = $this->buildOfficeSchedule();
		// 2024-01-02 — 10:00-15:00 (дата-исключение перекрывает weekday)
		$this->assertSame(1, $s->isWorkTime('2024-01-02', '12:00'));
		$this->assertSame(0, $s->isWorkTime('2024-01-02', '09:00'));
		// LEGACY-фича: правая граница 15:00 ВКЛЮЧЕНА.
		$this->assertSame(1, $s->isWorkTime('2024-01-02', '15:00'));
	}

	// =========================================================================
	// getStatus
	// =========================================================================

	public function testGetStatusReturnsIntForAlwaysOnSchedule(): void
	{
		// 24/7 расписание — почти всегда работает (00:00-23:59).
		// '00:00-24:00' валидатор не пропускает — поле HH ограничено 0..23.
		$s = $this->createSchedule(['name' => '24/7', 'start_date' => '2024-01-01']);
		$this->addEntry($s, ['date' => 'def', 'schedule' => '00:00-23:59']);
		$this->assertSame(1, $s->getStatus());
	}

	public function testGetStatusReturnsZeroForEmptySchedule(): void
	{
		$s = $this->createSchedule(['name' => 'Never', 'start_date' => '2024-01-01']);
		$this->addEntry($s, ['date' => 'def', 'schedule' => '-']);
		$this->assertSame(0, $s->getStatus());
	}

	// =========================================================================
	// metaAtTime
	// =========================================================================

	public function testMetaAtTimeReturnsEmptyBracesWhenOutsideWork(): void
	{
		$s = $this->buildOfficeSchedule();
		// нерабочее время → '{}'
		$this->assertSame('{}', $s->metaAtTime('2024-01-08', '07:00'));
		$this->assertSame('{}', $s->metaAtTime('2024-01-06', '10:00'));
	}

	public function testMetaAtTimeReturnsEmptyBracesForPlainScheduleNoMeta(): void
	{
		$s = $this->buildOfficeSchedule();
		// рабочее время, но meta не задано → '{}'
		$this->assertSame('{}', $s->metaAtTime('2024-01-08', '10:00'));
	}

	public function testMetaAtTimeReturnsRawMetaWhenPresent(): void
	{
		$s = $this->createSchedule(['name' => 'Meta', 'start_date' => '2024-01-01']);
		// Поддерживаются оба формата meta: legacy-псевдо-JSON `{user:pupkin}` и валидный JSON.
		$this->addEntry($s, ['date' => '5', 'schedule' => '08:00-17:00{user:pupkin}']);
		// 2024-01-05 — пятница
		$meta = $s->metaAtTime('2024-01-05', '10:00');
		$this->assertNotSame('{}', $meta, 'Должно вернуть meta а не пустые скобки');
		$this->assertStringContainsString('user', (string)$meta);
		$this->assertStringContainsString('pupkin', (string)$meta);
	}

	// =========================================================================
	// nextWorkingMeta
	// =========================================================================

	public function testNextWorkingMetaReturnsEmptyBracesWhenScheduleHasNoWork(): void
	{
		$s = $this->createSchedule(['name' => 'NoWork', 'start_date' => '2024-01-01']);
		$this->addEntry($s, ['date' => 'def', 'schedule' => '-']);
		$this->assertSame('{}', $s->nextWorkingMeta('2024-01-08', '10:00'));
	}

	public function testNextWorkingMetaReturnsEmptyBracesForPlainSchedule(): void
	{
		$s = $this->buildOfficeSchedule();
		// пятница 20:00 — следующее рабочее в понедельник, но meta нет → '{}'
		$this->assertSame('{}', $s->nextWorkingMeta('2024-01-05', '20:00'));
	}

	public function testNextWorkingMetaFindsRawMetaInFuture(): void
	{
		$s = $this->createSchedule(['name' => 'FutureMeta', 'start_date' => '2024-01-01']);
		$this->addEntry($s, ['date' => 'def', 'schedule' => '-']);
		$this->addEntry($s, ['date' => '5', 'schedule' => '08:00-17:00{duty:Иванов}']);
		// понедельник 10:00 — следующая meta будет в пятницу
		$meta = $s->nextWorkingMeta('2024-01-08', '10:00');
		$this->assertNotSame('{}', $meta);
		$this->assertStringContainsString('duty', (string)$meta);
	}
}
