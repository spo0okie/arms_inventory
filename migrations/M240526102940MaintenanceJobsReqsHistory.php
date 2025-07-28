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
	public function up()
	{
		$this->addColumnIfNotExists('maintenance_jobs_history', 'reqs_ids', $this->text());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('maintenance_jobs_history', 'reqs_ids');
	}
	
}
