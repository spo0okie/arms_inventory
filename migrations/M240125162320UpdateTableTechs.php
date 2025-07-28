<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240125162320UpdateTableTechs
 */
class M240125162320UpdateTableTechs extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('techs', 'domain_id', $this->integer(), true);
		$this->addColumnIfNotExists('techs', 'hostname', $this->string(128), true);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('techs', 'domain_id');
		$this->dropColumnIfExists('techs', 'hostname');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M240125162320UpdateTableTechs cannot be reverted.\n";

		return false;
	}
	*/
}
