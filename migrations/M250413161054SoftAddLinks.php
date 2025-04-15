<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250413161054SoftAddLinks
 */
class M250413161054SoftAddLinks extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExists('soft', 'links', $this->text()->comment('Links associated with the software'));
		$this->addColumnIfNotExists('soft', 'scans_id', $this->integer()->comment('ID of the software preview image'));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExists('soft', 'links');
		$this->dropColumnIfExists('soft', 'scans_id');
	}
}
