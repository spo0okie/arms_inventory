<?php
namespace app\migrations;
use app\models\OrgStruct;
use yii\db\Migration;

/**
 * Class m231020_074646_alter_table_org_struct
 */
class m231020_074646_alter_table_org_struct extends Migration
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
		$table = $this->db->getTableSchema('org_struct');
		if (!isset($table->columns['hr_id']))
			$this->renameColumn('org_struct', 'id', 'hr_id');
		
		if (!isset($table->columns['parent_hr_id']))
			$this->renameColumn('org_struct', 'pup', 'parent_hr_id');
		
		$this->addColumnIfNotExist('org_struct', 'parent_id', $this->integer()->null(), true);
		if (array_search('id', $table->primaryKey) !== false)
			$this->dropPrimaryKey('id', 'org_struct');
		$table = $this->db->getTableSchema('org_struct');
		if (array_search('org_id', $table->primaryKey) !== false)
			$this->dropPrimaryKey('org_id', 'org_struct');
		
		$this->addColumnIfNotExist('org_struct', 'id', $this->primaryKey(), true);
		foreach (OrgStruct::find()->all() as $item) $item->silentSave();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExist('org_struct', 'id');
		$this->dropColumnIfExist('org_struct', 'parent_id');
		$table = $this->db->getTableSchema('org_struct');
		if (array_search('id', $table->primaryKey) !== false)
			$this->dropPrimaryKey('id', 'org_struct');
		if (isset($table->columns['hr_id']))
			$this->renameColumn('org_struct', 'hr_id', 'id');
		if (isset($table->columns['parent_hr_id']))
			$this->renameColumn('org_struct', 'parent_hr_id', 'pup');
		$this->addPrimaryKey('PRIMARY', 'org_struct', ['id', 'org_id']);
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m231020_074646_alter_table_org_struct cannot be reverted.\n";

		return false;
	}
	*/
}
