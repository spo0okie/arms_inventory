<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230520_060357_alter_table_attaches
 */
class m230520_060357_alter_table_attaches extends Migration
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
		$this->addColumnIfNotExist('attaches', 'users_id', $this->integer()->null());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExist('attaches', 'users_id');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m230520_060357_alter_table_attaches cannot be reverted.\n";

		return false;
	}
	*/
}
