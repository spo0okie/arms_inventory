<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230511_094545_alter_table_login_journal
 */
class m230511_094545_alter_table_login_journal extends Migration
{
	
	
	function addColumnIfNotExist($table, $column, $type, $index = false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table, $column, $type);
			if ($index) $this->createIndex("idx-$table-$column", $table, $column);
			
		}
	}
	
	function dropColumnIfExist($table, $column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table, $column);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExist('login_journal', 'type', $this->integer()->defaultValue(0));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExist('login_journal', 'type');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m230511_094545_alter_table_login_journal cannot be reverted.\n";

		return false;
	}
	*/
}
