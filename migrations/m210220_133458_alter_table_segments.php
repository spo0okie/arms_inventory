<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210220_133458_alter_table_segments
 */
class m210220_133458_alter_table_segments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('segments');
	
		if (!isset($table->columns['code'])) {
			$this->addColumn('segments','code',$this->string());
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('segments');
	
		if (isset($table->columns['code'])) {
			$this->dropColumn('segments','code');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210220_133458_alter_table_segments cannot be reverted.\n";

        return false;
    }
    */
}
