<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230620_113027_create_table_ips_in_users
 */
class m230620_113027_create_table_ips_in_users extends Migration
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
		$this->addColumnIfNotExist('users','ips',$this->string(255));
		if (is_null($table = $this->db->getTableSchema('ips_in_users'))) {
			$this->createTable('ips_in_users', [
				'[[id]]'			=> $this->primaryKey(),		//ключ
				'[[ips_id]]'		=> $this->integer(),		//ip
				'[[users_id]]'		=> $this->integer(),		//arm
			],'ENGINE=InnoDB');
		
			$this->createIndex('{{%idx-ips_in_users-ips}}', 			'{{%ips_in_users}}', '[[ips_id]]');
			$this->createIndex('{{%idx-ips_in_users-users}}', 		'{{%ips_in_users}}', '[[users_id]]');
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (is_null($table = $this->db->getTableSchema('ips_in_users'))) {
			$this->dropTable('ips_in_users');
	    }
		$this->dropColumnIfExist('users','ips');
	}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230620_113027_create_table_ips_in_users cannot be reverted.\n";

        return false;
    }
    */
}
