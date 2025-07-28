<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250205141617CompsRescanQueue
 */
class M250205141617CompsRescanQueue extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->createTable('comps_rescan_queue', [
			'id' => $this->primaryKey(),
			'comps_id' => $this->integer()->notNull(),
			'soft_id' => $this->integer()->notNull(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
		], 'engine=InnoDB');
		$this->createIndex('comps_rescan_queue_comps', 'comps_rescan_queue', 'comps_id');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('comps_rescan_queue');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M250205141617CompsRescanQueue cannot be reverted.\n";

		return false;
	}
	*/
}
