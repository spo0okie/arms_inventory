<?php

use yii\db\Migration;

/**
 * Class m230526_181446_alter_table_services
 */
class m230526_181446_alter_table_services extends Migration
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
		$this->addColumnIfNotExist('services','infrastructure_user_id',$this->integer()->null());
		if (is_null($table = $this->db->getTableSchema('users_in_svc_infrastructure'))) {
			$this->createTable('users_in_svc_infrastructure', [
				'id' => $this->primaryKey(),
				'services_id' => $this->integer()->null(),
				'users_id' => $this->integer()->null(),
			]);
			
			$this->createIndex('idx-users_in_svc_infrastructure-services','users_in_svc_infrastructure','services_id');
			$this->createIndex('idx-users_in_svc_infrastructure-users','users_in_svc_infrastructure','users_id');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExist('services','infrastructure_user_id');
		if (!is_null($table = $this->db->getTableSchema('users_in_svc_infrastructure'))) {
			$this->dropTable('users_in_svc_infrastructure');
		}
	}
}
