<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m191208_173041_fix_many_2_many extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$table=$this->db->getTableSchema('{{%comps_in_services}}');
    	if (!count($table->primaryKey)) {
    		$this->addPrimaryKey('id','comps_in_services','id');
		    $this->alterColumn('comps_in_services','id',$this->integer()->notNull()->append('AUTO_INCREMENT'));
	    }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn('comps_in_services','id',$this->integer()->notNull());
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
