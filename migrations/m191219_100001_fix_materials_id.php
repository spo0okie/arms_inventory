<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m191219_100001_fix_materials_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$table=$this->db->getTableSchema('{{%materials}}');
	    if (!$table->getColumn('id')->autoIncrement) {
	    	if (!count($table->primaryKey)) {
			    $this->addPrimaryKey('id','materials','id');
		    }
		    $this->alterColumn('materials','id',$this->integer()->notNull()->append('AUTO_INCREMENT'));
	    }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn('materials','id',$this->integer()->notNull());
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
