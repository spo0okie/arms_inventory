<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M250514090728ContractsHistorySucessorFix extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->renameColumn('contracts_history','is_sucessor','is_successor');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->renameColumn('contracts_history','is_successor','is_sucessor');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250514090728ContractsHistorySucessorFix cannot be reverted.\n";

        return false;
    }
    */
}
