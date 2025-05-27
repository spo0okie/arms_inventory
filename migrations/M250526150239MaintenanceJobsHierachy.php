<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M250526150239MaintenanceJobsHierachy extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('maintenance_jobs', 'parent_id', $this->integer(),true);
		$this->addColumnIfNotExists('maintenance_jobs_history', 'parent_id', $this->integer(),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('maintenance_jobs_history', 'parent_id');
		$this->dropColumnIfExists('maintenance_jobs', 'parent_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250526150239MaintenanceJobsHierachy cannot be reverted.\n";

        return false;
    }
    */
}
