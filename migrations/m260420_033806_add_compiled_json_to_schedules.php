<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Добавляет колонку `schedules.compiled_json` для хранения
 * скомпилированного представления расписания в формате JSON.
 *
 * Подробности в modules/schedules/compile/compile.md.
 */
class m260420_033806_add_compiled_json_to_schedules extends ArmsMigration
{
	public function up()
	{
		$this->addColumnIfNotExists('schedules', 'compiled_json', $this->text()->null());
	}

	public function down()
	{
		$this->dropColumnIfExists('schedules', 'compiled_json');
	}
}
