<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240730162325CompsAdmins
 */
class M240730162325CompsAdmins extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('comps', 'platform_id', $this->integer(), true);
		$this->createMany2ManyTable('admins_in_comps', ['comps_id' => 'comps', 'users_id' => 'users']);
		$this->addColumnIfNotExists('comps_history', 'platform_id', $this->integer());
		$this->addColumnIfNotExists('comps_history', 'admins_ids', $this->text());
		$this->addColumnIfNotExists('tech_types', 'hide_menu', $this->boolean(), true);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('comps', 'platform_id');
		$this->dropTableIfExists('admins_in_comps');
		$this->dropColumnIfExists('comps_history', 'platform_id');
		$this->dropColumnIfExists('comps_history', 'admins_ids');
		$this->dropColumnIfExists('tech_types', 'hide_menu');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M240730162325CompsAdmins cannot be reverted.\n";

		return false;
	}
	*/
}
