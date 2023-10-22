<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210617_064650_alter_table_segments
 */
class m210617_064650_alter_table_segments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('segments');
		if (!isset($table->columns['history'])) {
			$this->addColumn('segments','history',$this->text());
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('segments');
		if (isset($table->columns['history'])) {
			$this->dropColumn('segments','history');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210617_064650_alter_table_segments cannot be reverted.\n";

        return false;
    }
    */
}
