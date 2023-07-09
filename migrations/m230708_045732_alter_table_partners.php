<?php

use yii\db\Migration;

/**
 * Class m230708_045732_alter_table_partners
 */
class m230708_045732_alter_table_partners extends Migration
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
		$this->addColumnIfNotExist('partners','prefix',$this->string('5'));
		$this->addColumnIfNotExist('techs','partners_id',$this->integer(),true);
		$this->addColumnIfNotExist('techs','uid',$this->string('16'),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('partners','prefix');
		$this->dropColumnIfExist('techs','partners_id');
		$this->dropColumnIfExist('techs','uid');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230708_045732_alter_table_partners cannot be reverted.\n";

        return false;
    }
    */
}
