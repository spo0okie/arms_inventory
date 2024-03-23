<?php
namespace app\migrations;
use app\migrations\arms\ArmsMigration;

/**
 * Class m210228_121450_table_ports
 */
class m210228_121450_table_ports extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('ports'))) {
			$this->createTable('ports', [
				'[[id]]'			=> $this->primaryKey(),						//ключ
				'[[techs_id]]'		=> $this->integer()->notNull(),				//к какому оборудованию принадлежит
				'[[name]]'			=> $this->string(32),					//номер порта или название (management/iLO)
				'[[comment]]'		=> $this->string(),							//comment
				'[[link_techs_id]]'	=> $this->integer()->null(),				//ссылка на другое оборудование
				'[[link_arms_id]]'	=> $this->integer()->null(),				//АРМ
				'[[link_ports_id]]'	=> $this->integer()->null(),				//другой порт
			],'ENGINE=InnoDB');
			$this->createIndex('{{%idx-ports-name}}',		 	'{{%ports}}', '[[name]]');
			$this->createIndex('{{%idx-ports-techs_id}}', 	'{{%ports}}', '[[techs_id]]');
			$this->createIndex('{{%idx-ports-link_techs_id}}','{{%ports}}', '[[link_techs_id]]');
			$this->createIndex('{{%idx-ports-link_arms_id}}',	'{{%ports}}', '[[link_arms_id]]');
			$this->createIndex('{{%idx-ports-link_ports_id}}','{{%ports}}', '[[link_ports_id]]');
			$this->addForeignKey('fk-ports_tech',			'ports','techs_id',		'techs',	'id',	'CASCADE');
			$this->addForeignKey('fk-ports_link_tech',	'ports','link_techs_id',	'techs',	'id',	'SET NULL');
			$this->addForeignKey('fk-ports_link_arms',	'ports','link_arms_id',	'arms',	'id',	'SET NULL');
			$this->addForeignKey('fk-ports_link_port',	'ports','link_ports_id',	'ports',	'id',	'SET NULL');
			
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table=$this->db->getTableSchema('{{%ports}}'))) {
			$this->dropFkIfExist('fk-ports_tech',		'ports');
			$this->dropFkIfExist('fk-ports_link_tech',	'ports');
			$this->dropFkIfExist('fk-ports_link_arms',	'ports');
			$this->dropFkIfExist('fk-ports_link_port',	'ports');
			$this->dropTable('{{%ports}}');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210228_121450_table_ports cannot be reverted.\n";

        return false;
    }
    */
}
