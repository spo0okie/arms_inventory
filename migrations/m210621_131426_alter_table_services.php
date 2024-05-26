<?php
namespace app\migrations;
use app\migrations\arms\ArmsMigration;

/**
 * Class m210621_131426_alter_table_services
 */
class m210621_131426_alter_table_services extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExists('services', 'parent_id', $this->integer()->null(),true);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExists('services', 'parent_id');
	}
}

