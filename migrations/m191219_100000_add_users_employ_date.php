<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m191219_100000_add_users_employ_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$table=$this->db->getTableSchema('{{%users}}');
	    if (!isset($table->columns['employ_date'])) {
	    	$this->addColumn('{{%users}}','employ_date',$this->string(16)->null());
	    }
	    if (!isset($table->columns['resign_date'])) {
		    $this->addColumn('{{%users}}','resign_date',$this->string(16)->null());
	    }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    if (isset($table->columns['resign_date'])) {
		    $this->dropColumn('{{%users}}','resign_date');
	    }
	    if (isset($table->columns['employ_date'])) {
		    $this->dropColumn('{{%users}}','employ_date');
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
