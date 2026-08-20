<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M231109084405FixAutoincrement
 *
 * Наследуется от ArmsMigration: `set @max_id=...; update ...` шлётся одной строкой, а PDO
 * проверяет результат только первого стейтмента - ошибки остальных без
 * ArmsMigration::execute() молча теряются (plans/bugs20260820.md, сторож
 * tests/unit/db/MigrationSqlHygieneTest).
 */
class M231109084405FixAutoincrement extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$techs = $this->getDb()->getTableSchema('techs');
		foreach ($techs->foreignKeys as $foreignKey => $data)
			$this->dropForeignKey($foreignKey, 'techs');
		
		$this->execute('set @max_id=(select max(id) from hw_ignore); update hw_ignore set id=@max_id+1 where id=0;');
		$this->alterColumn('hw_ignore', 'id', $this->integer()->append(' AUTO_INCREMENT'));
		$this->execute('set @max_id=(select max(id) from tech_states); update tech_states set id=@max_id+1 where id=0;');
		$this->alterColumn('tech_states', 'id', $this->integer()->append(' AUTO_INCREMENT'));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		echo "M231109084405FixAutoincrement no need to be reverted.\n";
		
		return true;
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M231109084405FixAutoincrement cannot be reverted.\n";

		return false;
	}
	*/
}
