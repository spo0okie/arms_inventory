<?php

use yii\db\Migration;

/**
 * Class m210222_174038_alter_table_net_ips
 */
class m210222_174038_alter_table_net_ips extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('net_ips');
		if (!isset($table->columns['name'])) {
			$this->addColumn('net_ips','name',$this->string()->Null());
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('net_ips');
		if (isset($table->columns['name'])) {
			$this->dropColumn('net_ips','name');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210224_174038_alter_table_net_ips cannot be reverted.\n";

        return false;
    }
    */
}
