<?php

use yii\db\Migration;

/**
 * Class m230512_124513_alter_table_login_journal
 */
class m230512_124513_alter_table_login_journal extends Migration
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
		$this->addColumnIfNotExist('login_journal','local_time',$this->integer()->null());
		$this->alterColumn('login_journal','users_id',$this->integer()->null());
		$this->alterColumn('login_journal','comps_id',$this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('login_journal','local_time');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230512_124513_alter_table_login_journal cannot be reverted.\n";

        return false;
    }
    */
}
