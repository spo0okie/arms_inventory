<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Regression-тест: end_date без времени должна включать ВЕСЬ этот день.
 *
 * Сценарий из прода:
 *  - есть основное расписание с meta "Иванов" на пятницу;
 *  - заведён override на пн-пт (`start_date=2024-01-08`, `end_date=2024-01-12`) с meta "Петров";
 *  - в пятницу запрос на дежурного должен вернуть meta override-а ("Петров"),
 *    а не базового ("Иванов").
 *
 * До фикса compiled-рантайм трактовал `end_tsm = strToTsm('2024-01-12')` как
 * `2024-01-12 00:00` UTC и через `tsm < end_tsm` исключал всю пятницу из override-а.
 * После фикса `end_date` без времени расширяется до конца дня (+1440 мин).
 */
class OverrideEndDateBoundaryTest extends Unit
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
		$model = new Schedules(array_merge(['name' => 'Boundary'], $attrs));
		$this->assertTrue($model->save(), 'schedule save: ' . json_encode($model->errors));
		$this->createdIds[] = $model->id;
		return $model;
	}

	private function addEntry(Schedules $schedule, array $attrs): SchedulesEntries
	{
		$entry = new SchedulesEntries(array_merge(['schedule_id' => $schedule->id], $attrs));
		$this->assertTrue($entry->save(), 'entry save: ' . json_encode($entry->errors));
		return $entry;
	}

	public function testOverrideEndDateIncludesEntireFinalDay(): void
	{
		// Основное расписание: ежедневно 08:00-17:00, meta="Иванов".
		$main = $this->createSchedule(['name' => 'Main', 'start_date' => '2024-01-01']);
		$this->addEntry($main, ['date' => 'def', 'schedule' => '08:00-17:00{"duty":"Иванов"}']);

		// Override на пн-пт (2024-01-08..2024-01-12) с другим дежурным.
		$override = $this->createSchedule([
			'name'        => 'WeekOverride',
			'parent_id'   => $main->id,
			'override_id' => $main->id,
			'start_date'  => '2024-01-08',
			'end_date'    => '2024-01-12',
		]);
		$this->addEntry($override, ['date' => 'def', 'schedule' => '08:00-17:00{"duty":"Петров"}']);

		// Понедельник 10:00 — должен быть Петров.
		$mondayMeta = $main->metaAtTime('2024-01-08', '10:00');
		$this->assertStringContainsString('Петров', (string)$mondayMeta, 'Понедельник внутри override');

		// Пятница 16:00 (последний день override) — тоже должен быть Петров.
		$fridayMeta = $main->metaAtTime('2024-01-12', '16:00');
		$this->assertStringContainsString(
			'Петров',
			(string)$fridayMeta,
			'Пятница (end_date) должна целиком попадать в override, а не уходить в main'
		);

		// Суббота 10:00 — уже основное расписание (Иванов).
		$saturdayMeta = $main->metaAtTime('2024-01-13', '10:00');
		$this->assertStringContainsString('Иванов', (string)$saturdayMeta, 'Суббота уже после override');
	}
}
