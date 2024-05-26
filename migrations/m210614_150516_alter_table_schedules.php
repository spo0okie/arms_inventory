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
		$this->addColumnIfNotExists('schedules','parent_id',$this->integer()->Null(),true);
		$this->addColumnIfNotExists('schedules','history',$this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('schedules','parent_id');
		$this->dropColumnIfExists('schedules','history');
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
