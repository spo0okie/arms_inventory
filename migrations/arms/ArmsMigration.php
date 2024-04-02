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
	
	function dropFkIfExist($name,$table)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->foreignKeys[$name])) {
			$this->dropForeignKey($name,$table);
		}
	}
	
	function tableExists($table)
	{
		$tableSchema = $this->db->getTableSchema($table);
		return !is_null($tableSchema);
	}
	
	function dropTableIfExists($table)
	{
		if ($this->tableExists($table))
			$this->dropTable($table);
	}
	
	/**
	 * @param $tableName string maintenance_reqs_in_techs
	 * @param $links array ['techs_id'=>'techs','reqs_id'=>'maintenance_reqs']
	 */
	function createMany2ManyTable(string $tableName, array $links)
	{
		//если у нас числовые ключи
		if (isset($links[0])) {
			$keys=$links;
			$tables=null;
		} else {
			$keys=array_keys($links);
			$tables=array_values($links);
		}
		$this->dropTableIfExists($tableName);
		$this->createTable($tableName,[
			'id'=>$this->primaryKey(),
			$keys[0]=>$this->integer(),
			$keys[1]=>$this->integer(),
		],'engine=InnoDB');
		$this->createIndex($tableName.'-'.$keys[0],$tableName,$keys[0]);
		$this->createIndex($tableName.'-'.$keys[1],$tableName,$keys[1]);
		$this->createIndex($tableName.'-m2m',$tableName,$keys,true);
		
		//для генератора моделей полезно чтобы были ссылки в БД
		if (is_array($tables)) {
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


}
