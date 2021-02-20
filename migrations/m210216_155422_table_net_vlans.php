<?php

use yii\db\Migration;

/**
 * Class m210216_155422_table_net_vlans
 */
class m210216_155422_table_net_vlans extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		if (is_null($table = $this->db->getTableSchema('net_vlans'))) {
			$this->createTable('net_vlans', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[name]]'			=> $this->string(),			//имя
				'[[vlan]]'			=> $this->integer(),		//номер вилана
				'[[domain_id]]'		=> $this->integer()->null(),//домен
				'[[segment_id]]'	=> $this->integer()->null(),//сегмент ИТ
				'[[comment]]'		=> $this->text(),			//комментарий
			],'ENGINE=InnoDB');
			$this->createIndex('{{%idx-net_vlans-domain_id}}', 			'{{%net_vlans}}', '[[domain_id]]');
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('{{%net_vlans}}'))) $this->dropTable('{{%net_vlans}}');
	}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210216_155422_table_net_vlans cannot be reverted.\n";

        return false;
    }
    */
}
