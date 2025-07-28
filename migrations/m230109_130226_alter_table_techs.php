<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230109_130226_alter_table_techs
 */
class m230109_130226_alter_table_techs extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('techs');
		if (!isset($table->columns['departments_id'])) {
			$this->addColumn('techs', 'departments_id', $this->integer());
			$this->createIndex('idx-techs-departments_id', 'techs', 'departments_id');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('techs');
		if (isset($table->columns['departments_id'])) {
			$this->dropColumn('techs', 'departments_id');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m230109_130226_alter_table_techs cannot be reverted.\n";

		return false;
	}
	*/
}
