<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m200121_080000_add_users_auth_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$table=$this->db->getTableSchema('users');
	    if (!isset($table->columns['auth_key'])) {
	    	$this->addColumn('users','auth_key',$this->string(255)->null());
	    }
	    if (!isset($table->columns['access_token'])) {
		    $this->addColumn('users','access_token',$this->string(255)->null());
	    }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('users');
	    if (isset($table->columns['auth_key'])) {
		    $this->dropColumn('users','auth_key');
	    }
	    if (isset($table->columns['access_token'])) {
		    $this->dropColumn('users','access_token');
	    }
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191208_173041_fix_many_2_many cannot be reverted.\n";

        return false;
    }
    */
}
