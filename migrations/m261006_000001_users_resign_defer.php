<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Откладывание увольнения сотрудника на стороне инвентаризации.
 *
 * Uvolen приходит из кадровой системы и ею же перезаписывается, поэтому
 * решение «пока не считать уволенным» хранится отдельно:
 *  - resign_defer       - откладывание включено;
 *  - resign_defer_until - до какой даты (включительно); NULL - бессрочно.
 *
 * Итоговый признак «уволен» (Users::$resigned) не хранится: он меняется сам
 * с наступлением даты и вычисляется на лету (в SQL - Users::resignedExpression()).
 *
 * Колонки зеркалируются в журнал users_history.
 */
class m261006_000001_users_resign_defer extends ArmsMigration
{
	public function up()
	{
		foreach (['users','users_history'] as $table) {
			if (!$this->tableExists($table)) continue;
			$this->addColumnIfNotExists(
				$table,
				'resign_defer',
				$table==='users'
					?$this->boolean()->notNull()->defaultValue(0)->comment('Отложить увольнение')
					:$this->boolean()
			);
			$this->addColumnIfNotExists(
				$table,
				'resign_defer_until',
				$this->date()->null()->comment('Отложить увольнение до (включительно); пусто - бессрочно')
			);
		}
	}

	public function down()
	{
		foreach (['users','users_history'] as $table) {
			if (!$this->tableExists($table)) continue;
			$this->dropColumnIfExists($table,'resign_defer_until');
			$this->dropColumnIfExists($table,'resign_defer');
		}
	}
}
