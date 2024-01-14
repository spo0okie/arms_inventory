<?php

namespace app\migrations\arms;

use yii\db\Migration;

/**
 * Class M231226142737CreateTableJobs
 */
class ArmsMigration extends Migration
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
	 * @param $tableName string maintenance_reqs_in_techs
	 * @param $links array ['techs_id'=>'techs','reqs_id'=>'maintenance_reqs']
	 */
	function createMany2ManyTable(string $tableName, array $links)
	{
		$keys=array_keys($links);
		$tables=array_values($links);
		$this->createTable($tableName,[
			'id'=>$this->primaryKey(),
			$keys[0]=>$this->integer(),
			$keys[1]=>$this->integer(),
		],'engine=InnoDB');
		$this->createIndex($tableName.'-'.$keys[0],$tableName,$keys[0]);
		$this->createIndex($tableName.'-'.$keys[1],$tableName,$keys[1]);
		$this->createIndex($tableName.'-m2m',$tableName,$keys,true);
		$this->addForeignKey(
			'fk-'.$tableName.'-'.$keys[0],
			$tableName,
			$keys[0],
			$tables[0],
			'id'
		);
		$this->addForeignKey(
			'fk-'.$tableName.'-'.$keys[1],
			$tableName,
			$keys[1],
			$tables[1],
			'id'
		);
	}


}
