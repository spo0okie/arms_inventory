<?php

use yii\db\Migration;

/**
 * Class m230802_162919_alter_table_networks
 */
class m230802_162919_alter_table_networks extends Migration
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
		$this->addColumnIfNotExist('networks','notepad',$this->text());
		
		$this->addColumnIfNotExist('services','vm_cores',$this->integer());
		$this->addColumnIfNotExist('services','vm_ram',$this->integer());
		$this->addColumnIfNotExist('services','vm_hdd',$this->integer());
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('networks','notepad');
	
		$this->dropColumnIfExist('services','vm_cores');
		$this->dropColumnIfExist('services','vm_ram');
		$this->dropColumnIfExist('services','vm_hdd');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230802_162919_alter_table_networks cannot be reverted.\n";

        return false;
    }
    */
}
