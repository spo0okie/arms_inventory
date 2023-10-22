<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230903_074600_sync_prepare_4
 */
class m230903_074600_sync_prepare_4 extends Migration
{
	function addColumnIfNotExist($table,$column,$type,$index=false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table,$column,$type);
			if ($index) $this->createIndex("idx-$table-$column",$table,$column);
			
		}
	}
	
	function dropColumnIfExist($table,$column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table,$column);
		}
	}
	
	function dropFkIfExist($table,$name)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->foreignKeys[$name])) {
			$this->dropForeignKey($name,$table);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExist('partners','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('partners','updated_by',$this->string(32));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExist('partners','updated_at');
		$this->dropColumnIfExist('partners','updated_by');
	}
}
