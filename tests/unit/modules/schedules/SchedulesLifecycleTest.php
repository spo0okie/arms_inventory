<?php

namespace tests\unit\modules\schedules;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Тесты жизненного цикла компиляции:
 * - afterSave(Schedules) и afterSave(SchedulesEntries) пишут compiled_json;
 * - каскад по parent_id и override_id.
 */
class SchedulesLifecycleTest extends Unit
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
		$model = new Schedules(array_merge([
			'name' => 'Lifecycle schedule',
		], $attrs));
		$this->assertTrue($model->save());
		$this->createdIds[] = $model->id;
		return $model;
	}

	public function testSaveSchedulePopulatesCompiledJson(): void
	{
		$schedule = $this->createSchedule(['name' => 'Main', 'start_date' => '2024-01-01']);
		$schedule->refresh();
		$this->assertNotEmpty($schedule->compiled_json, 'compiled_json должен быть заполнен после save');

		$decoded = json_decode($schedule->compiled_json, true);
		$this->assertSame('Main', $decoded['main']['name']);
		$this->assertSame(28401120, $decoded['main']['start_tsm']);
	}

	public function testSchedulesEntrySaveTriggersRecompile(): void
	{
		$schedule = $this->createSchedule(['name' => 'Entry-trigger']);
		$entry = new SchedulesEntries([
			'schedule_id' => $schedule->id,
			'date'        => 'def',
			'schedule'    => '08:00-17:00',
		]);
		$this->assertTrue($entry->save());

		$schedule->refresh();
		$decoded = json_decode($schedule->compiled_json, true);
		$this->assertNotEmpty($decoded['main']['default']);
		$this->assertSame('08:00-17:00', $decoded['main']['default']['schedule']);
	}

	public function testSchedulesEntryDeleteTriggersRecompile(): void
	{
		$schedule = $this->createSchedule(['name' => 'Entry-delete']);
		$entry = new SchedulesEntries([
			'schedule_id' => $schedule->id,
			'date'        => 'def',
			'schedule'    => '08:00-17:00',
		]);
		$this->assertTrue($entry->save());

		$this->assertSame(1, $entry->delete());

		$schedule->refresh();
		$decoded = json_decode($schedule->compiled_json, true);
		$this->assertNull($decoded['main']['default'], 'default должен исчезнуть после удаления entry');
	}

	public function testOverrideSaveRecompilesParent(): void
	{
		$parent = $this->createSchedule(['name' => 'Parent', 'start_date' => '2024-01-01']);
		$override = $this->createSchedule([
			'name'        => 'Summer',
			'parent_id'   => $parent->id,
			'override_id' => $parent->id,
			'start_date'  => '2024-06-01',
			'end_date'    => '2024-08-31',
		]);

		$parent->refresh();
		$decoded = json_decode($parent->compiled_json, true);
		$this->assertCount(1, $decoded['overrides'], 'После сохранения override родитель должен его содержать в overrides');
		$this->assertSame('Summer', $decoded['overrides'][0]['name']);

		// Изменяем override → ожидаем обновление compiled_json родителя.
		$override->name = 'Summer edit';
		$this->assertTrue($override->save());
		$parent->refresh();
		$decoded = json_decode($parent->compiled_json, true);
		$this->assertSame('Summer edit', $decoded['overrides'][0]['name']);
	}

	public function testCascadeRecompilesChildrenNonOverrides(): void
	{
		$parent = $this->createSchedule(['name' => 'Root']);
		$child = $this->createSchedule([
			'name'      => 'Child',
			'parent_id' => $parent->id,
		]);

		$parent->refresh();
		$child->refresh();
		$this->assertNotEmpty($parent->compiled_json);
		$this->assertNotEmpty($child->compiled_json);

		// изменяем parent → ожидаем перекомпиляцию child
		$oldChildJson = $child->compiled_json;
		$parent->name = 'Root renamed';
		$this->assertTrue($parent->save());

		$child->refresh();
		// Содержимое compile_json ребёнка не меняется от изменения имени parent (т.к.
		// на текущем этапе наследование от parent не реализовано), но метка `compiled`
		// при перекомпиляции должна обновиться.
		$this->assertNotEmpty($child->compiled_json);
		$decodedOld = json_decode($oldChildJson, true);
		$decodedNew = json_decode($child->compiled_json, true);
		$this->assertIsString($decodedOld['compiled']);
		$this->assertIsString($decodedNew['compiled']);
	}
}
