<?php

use yii\db\Migration;

/**
 * Class m210831_093619_alter_table_users
 */
class m210831_093619_alter_table_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('users');
		if (!isset($table->columns['notepad']))
			$this->addColumn('users','notepad',$this->text());
		
		$this->alterColumn('arms','comp_id',$this->integer()->null());
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('users');
		if (isset($table->columns['notepad']))
			$this->dropColumn('users','notepad',$this->text());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210831_093619_alter_table_users cannot be reverted.\n";

        return false;
    }
    */
}
