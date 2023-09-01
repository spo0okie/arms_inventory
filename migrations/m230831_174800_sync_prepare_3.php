<?php

use yii\db\Migration;

/**
 * Class m230831_174800_sync_prepare_3
 */
class m230831_174800_sync_prepare_3 extends Migration
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
		$this->alterColumn('lic_types','created_at',$this->timestamp());
		$this->renameColumn('lic_types','created_at','updated_at');
		$this->alterColumn('lic_groups','created_at',$this->timestamp());
		$this->renameColumn('lic_groups','created_at','updated_at');
		$this->addColumnIfNotExist('lic_groups','updated_by',$this->string(32));
		$this->addColumnIfNotExist('lic_types','updated_by',$this->string(32));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('lic_groups','updated_by');
		$this->dropColumnIfExist('lic_types','updated_by');
		$this->renameColumn('lic_groups','updated_at','created_at');
		$this->renameColumn('lic_types','updated_at','created_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230831_174800_sync_prepare_3 cannot be reverted.\n";

        return false;
    }
    */
}
