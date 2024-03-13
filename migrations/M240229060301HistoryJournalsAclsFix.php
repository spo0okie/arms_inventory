<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240229060301HistoryJournalsAclsFix
 */
class M240229060301HistoryJournalsAclsFix extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->dropColumnIfExist('schedules_entries_history','notepad');
		$this->addColumnIfNotExist('schedules_entries_history','history',$this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('schedules_entries_history','history');
		$this->addColumnIfNotExist('schedules_entries_history','notepad',$this->text());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240229060301HistoryJournalsAclsFix cannot be reverted.\n";

        return false;
    }
    */
}
