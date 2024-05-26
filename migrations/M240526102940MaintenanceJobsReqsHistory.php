<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240526102940MaintenanceJobsReqsHistory
 */
class M240526102940MaintenanceJobsReqsHistory extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('maintenance_jobs_history','reqs_ids',$this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('maintenance_jobs_history','reqs_ids');
    }

}
