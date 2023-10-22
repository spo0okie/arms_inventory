<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m200317_033238_create_user_in_services
 */
class m200317_033238_create_user_in_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    if (is_null($table = $this->db->getTableSchema('users_in_services'))) {
		    $this->createTable('users_in_services', [
			    '[[id]]'			=> $this->primaryKey(),		//ключ
			    '[[service_id]]'	=> $this->integer(),		//сервиса
			    '[[user_id]]'	=> $this->integer(),		//сервиса
		    ],'ENGINE=InnoDB');
		
		    $this->createIndex('{{%idx-users_in_services_uid}}', 			'{{%users_in_services}}', '[[user_id]]');
		    $this->createIndex('{{%idx-users_in_services_sid}}', 			'{{%users_in_services}}', '[[service_id]]');
	    }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    if (!is_null($table=$this->db->getTableSchema('{{%users_in_services}}'))) $this->dropTable('{{%users_in_services}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200317_033238_create_user_in_services cannot be reverted.\n";

        return false;
    }
    */
}
