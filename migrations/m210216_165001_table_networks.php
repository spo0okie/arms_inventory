<?php

use yii\db\Migration;

/**
 * Class m210216_165001_table_networks
 */
class m210216_165001_table_networks extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		if (is_null($table = $this->db->getTableSchema('networks'))) {
			$this->createTable('networks', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[name]]'			=> $this->string(),			//имя
				'[[vlan_id]]'		=> $this->integer(),		//вилан
				'[[text_addr]]'		=> $this->string(32),	//адрес
				'[[addr]]'			=> $this->integer(10)->unsigned(),		//адрес
				'[[mask]]'			=> $this->integer(10)->unsigned(),		//маска
				'[[router]]'		=> $this->integer(10)->unsigned(),		//шлюз
				'[[dhcp]]'			=> $this->integer(10)->unsigned(),		//dhcp сервер
				'[[comment]]'		=> $this->text(),			//комментарий
			],'ENGINE=InnoDB');
			$this->createIndex('{{%idx-networks-vlan_id}}', 			'{{%networks}}', '[[vlan_id]]');
			$this->createIndex('{{%idx-networks-addr}}',  			'{{%networks}}', '[[addr]]');
			$this->createIndex('{{%idx-networks-text_addr}}',  		'{{%networks}}', '[[text_addr]]');
			$this->createIndex('{{%idx-networks-mask}}',  			'{{%networks}}', '[[mask]]');
			$this->createIndex('{{%idx-networks-router}}',  			'{{%networks}}', '[[router]]');
			$this->createIndex('{{%idx-networks-dhcp}}',  			'{{%networks}}', '[[dhcp]]');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('{{%networks}}'))) $this->dropTable('{{%networks}}');
	}


    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210216_165001_table_networks cannot be reverted.\n";

        return false;
    }
    */
}
