<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M241225123824MaintenanceDescr
 */
class M241225123824MaintenanceDescr extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->dropIndexIfExists('maintenance_jobs-description', 'maintenance_jobs');
		$this->dropIndexIfExists('maintenance_reqs-description', 'maintenance_reqs');
		$this->alterColumn('maintenance_jobs', 'description', $this->text());
		$this->alterColumn('maintenance_reqs', 'description', $this->text());
		$this->createIndex('maintenance_jobs-description', 'maintenance_jobs', ['description(1024)'], false);
		$this->createIndex('maintenance_reqs-description', 'maintenance_reqs', ['description(1024)'], false);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropIndexIfExists('maintenance_jobs-description', 'maintenance_jobs');
		$this->dropIndexIfExists('maintenance_reqs-description', 'maintenance_reqs');
		$this->alterColumn('maintenance_jobs', 'description', $this->string(1024));
		$this->alterColumn('maintenance_reqs', 'description', $this->string(1024));
		$this->createIndex('maintenance_jobs-description', 'maintenance_jobs', ['description'], false);
		$this->createIndex('maintenance_reqs-description', 'maintenance_reqs', ['description'], false);
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M241225123824MaintenanceDescr cannot be reverted.\n";

		return false;
	}
	*/
}
