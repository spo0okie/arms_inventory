<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M250805150713LoginJournalIndexes extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->createIndex('login_journal_comp_name_idx','login_journal','comp_name');
		$this->createIndex('login_journal_user_login_idx','login_journal','user_login');
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
		$this->dropIndex('login_journal_comp_name_idx', 'login_journal');
		$this->dropIndex('login_journal_user_login_idx', 'login_journal');
	}
	
}
