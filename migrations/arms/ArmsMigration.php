<?php

namespace app\migrations\arms;

use app\helpers\ArrayHelper;
use yii\db\Migration;

/**
 * Class M231226142737CreateTableJobs
 */
class ArmsMigration extends Migration
{
	function addColumnIfNotExists($table, $column, $type, $index=false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table,$column,$type);
			if ($index) $this->createIndex("idx-$table-$column",$table,$column);
			
		}
	}
	
	function dropColumnIfExists($table, $column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table,$column);
		}
	}
	
	function dropFkIfExists($name, $table)
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
	
	function indexExists($name,$table) {
		$command = $this->getDb()->createCommand('show index from '.$table);
		$tableIndexes = $command->queryAll();
		return count(ArrayHelper::findByField($tableIndexes,'Key_name',$name));
	}
	
	public function getTableStatus($table) {
		$command = $this->getDb()->createCommand("show table status where Name = '$table'");
		return $command->queryOne();
	}
	
	function dropIndexIfExists($name, $table)
	{
		if ($this->indexExists($name,$table))
			$this->dropIndex($name,$table);
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
	
	
	/** @noinspection SqlResolve */
	public function convertTableToInnoDb($table) {
		$status=$this->getTableStatus($table);
		if (strtolower($status['Engine']??'')!=='innodb') {
			$this->db->createCommand("alter table `$table` engine = InnoDB")->execute();
		}
	}

}
