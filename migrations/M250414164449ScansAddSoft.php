<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250414164449ScansAddSoft
 */
class M250414164449ScansAddSoft extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExists('scans', 'soft_id', $this->integer()->comment('Soft ID associated with the scan'),true);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExists('scans', 'soft_id');
	}
}
