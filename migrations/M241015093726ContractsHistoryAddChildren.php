<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M241015093726ContractsHistoryAddChildren
 */
class M241015093726ContractsHistoryAddChildren extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('contracts_history', 'children_ids', $this->text());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('contracts_history', 'children_ids');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M241015093726ContractsHistoryAddChildren cannot be reverted.\n";

		return false;
	}
	*/
}
