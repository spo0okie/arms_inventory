<?php
namespace app\migrations;
use app\migrations\arms\ArmsMigration;

/**
 * Class m210614_150516_alter_table_schedules
 */
class m210614_150516_alter_table_schedules extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExist('schedules','parent_id',$this->integer()->Null(),true);
		$this->addColumnIfNotExist('schedules','history',$this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('schedules','parent_id');
		$this->dropColumnIfExist('schedules','history');
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
