<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Class M231217071124AlterTableSegments
 */
class M231217071124AlterTableSegments extends Migration
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
		$this->addColumnIfNotExist('segments', 'archived', $this->boolean(), true);
		$this->addColumnIfNotExist('segments', 'links', $this->text());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExist('networks', 'archived');
		$this->dropColumnIfExist('networks', 'links');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M231217071124AlterTableSegments cannot be reverted.\n";

		return false;
	}
	*/
}
