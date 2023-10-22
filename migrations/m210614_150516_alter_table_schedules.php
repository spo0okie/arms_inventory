<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210614_150516_alter_table_schedules
 */
class m210614_150516_alter_table_schedules extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('schedules');
		if (!isset($table->columns['parent_id'])) {
			$this->addColumn('schedules','parent_id',$this->integer()->Null());
			$this->createIndex('{{%idx-schedules_parent_id}}', '{{%schedules}}', '[[parent_id]]');
		}
		if (!isset($table->columns['history'])) {
			$this->addColumn('schedules','history',$this->text());
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('schedules');
		if (isset($table->columns['parent_id'])) {
			$this->dropColumn('schedules','parent_id');
			$this->dropIndex('{{%idx-schedules_parent_id}}', '{{%schedules}}');
		}
		if (isset($table->columns['history'])) {
			$this->dropColumn('schedules','history');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210614_150516_alter_table_schedules cannot be reverted.\n";

        return false;
    }
    */
}
