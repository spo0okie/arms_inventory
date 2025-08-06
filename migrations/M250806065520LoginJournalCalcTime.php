<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M250806065520LoginJournalCalcTime extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->addColumnIfNotExists('login_journal','created_at', $this->dateTime());
		$this->addColumnIfNotExists('login_journal','calc_time', $this->timestamp());
		$this->createIndex('login_journal_time_idx','login_journal','time');
		$this->createIndex('login_journal_type_idx','login_journal','type');
		$this->createIndex('login_journal_calc_time_idx','login_journal','calc_time');
		$this->execute("UPDATE login_journal SET calc_time = time");
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
		$this->dropColumnIfExists('login_journal','created_at');
		$this->dropColumnIfExists('login_journal','calc_time');
		$this->dropIndex('login_journal_time_idx','login_journal');
		$this->dropIndex('login_journal_type_idx','login_journal');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250806065520LoginJournalCalcTime cannot be reverted.\n";

        return false;
    }
    */
}
