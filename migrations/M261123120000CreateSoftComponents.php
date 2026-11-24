<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/** Возможные родители разделяемого компонента ПО. */
class M261123120000CreateSoftComponents extends ArmsMigration
{
	/** Создаёт связи компонентов с продуктами. */
	public function up()
	{
		// Хелпер пересоздаёт таблицу, поэтому бережём уже записанные связи.
		if (!$this->tableExists('soft_components')) {
			$this->createMany2ManyTable('soft_components', ['component_id', 'parent_id']);
		}
	}

	/** Удаляет таблицу вложений. */
	public function down()
	{
		$this->dropTableIfExists('soft_components');
	}
}
