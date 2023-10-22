<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230527_052818_add_external_links
 */
class m230527_052818_add_external_links extends Migration
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
	
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExist('services','external_links',$this->text()->null());
		$this->addColumnIfNotExist('techs','external_links',$this->text()->null());
		$this->addColumnIfNotExist('comps','external_links',$this->text()->null());
		$this->addColumnIfNotExist('users','external_links',$this->text()->null());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExist('services','external_links');
		$this->dropColumnIfExist('techs','external_links');
		$this->dropColumnIfExist('comps','external_links');
		$this->dropColumnIfExist('users','external_links');
	}
}
