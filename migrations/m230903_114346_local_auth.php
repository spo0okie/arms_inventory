<?php

use yii\db\Migration;

/**
 * Class m230903_114346_local_auth
 */
class m230903_114346_local_auth extends Migration
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
		$this->addColumnIfNotExist('users','password',$this->string());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExist('users','password');
	}
}
