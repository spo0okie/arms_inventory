<?php

use yii\db\Migration;

/**
 * Class m210220_171805_create_table_netAddr
 */
class m210220_171805_create_table_netAddr extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		if (is_null($table = $this->db->getTableSchema('net_ips'))) {
			$this->createTable('net_ips', [
				'[[id]]'			=> $this->primaryKey(),						//ключ
				'[[addr]]'			=> $this->integer(10)->unsigned(),	//адрес
				'[[mask]]'			=> $this->integer(),						//префикс
				'[[text_addr]]'		=> $this->string(32),					//адрес текстовый
				'[[comment]]'		=> $this->string(),							//comment чо
			],'ENGINE=InnoDB');
			$this->createIndex('{{%idx-net_ips-addr}}', 		'{{%net_ips}}', '[[addr]]');
			$this->createIndex('{{%idx-net_ips-mask}}', 		'{{%net_ips}}', '[[mask]]');
			$this->createIndex('{{%idx-net_ips-text_addr}}',	'{{%net_ips}}', '[[text_addr]]');
		}
		
		
		if (is_null($table = $this->db->getTableSchema('ips_in_comps'))) {
			$this->createTable('ips_in_comps', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[ips_id]]'		=> $this->integer(),		//ip
				'[[comps_id]]'		=> $this->integer(),		//arm
			],'ENGINE=InnoDB');
			
			$this->createIndex('{{%idx-ips_in_comps-ips}}', 			'{{%ips_in_comps}}', '[[ips_id]]');
			$this->createIndex('{{%idx-ips_in_comps-comps}}', 			'{{%ips_in_comps}}', '[[comps_id]]');
		}
		
		if (is_null($table = $this->db->getTableSchema('ips_in_techs'))) {
			$this->createTable('ips_in_techs', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[ips_id]]'		=> $this->integer(),		//ip
				'[[techs_id]]'		=> $this->integer(),		//arm
			],'ENGINE=InnoDB');
			
			$this->createIndex('{{%idx-ips_in_techs-ips}}', 			'{{%ips_in_techs}}', '[[ips_id]]');
			$this->createIndex('{{%idx-ips_in_techs-techs}}', 		'{{%ips_in_techs}}', '[[techs_id]]');
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('{{%net_ips}}'))) $this->dropTable('{{%net_ips}}');
		if (!is_null($table=$this->db->getTableSchema('{{%ips_in_comps}}'))) $this->dropTable('{{%ips_in_comps}}');
		if (!is_null($table=$this->db->getTableSchema('{{%ips_in_techs}}'))) $this->dropTable('{{%ips_in_techs}}');
	}
	
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210220_171805_create_table_netAddr cannot be reverted.\n";

		return false;
	}
	*/
}
