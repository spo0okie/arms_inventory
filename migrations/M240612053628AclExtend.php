<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240612053628AclExtend
 */
class M240612053628AclExtend extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->convertTableToInnoDb('aces');
		$this->convertTableToInnoDb('acls');
		$this->convertTableToInnoDb('services');
		$this->convertTableToInnoDb('networks');
		$this->createMany2ManyTable('services_in_aces', [
			'aces_id' => 'aces',
			'services_id' => 'services'
		]);
		$this->createMany2ManyTable('networks_in_aces', [
			'aces_id' => 'aces',
			'networks_id' => 'networks'
		]);
		$this->addColumnIfNotExists('aces_history', 'services_ids', $this->text());
		$this->addColumnIfNotExists('aces_history', 'networks_ids', $this->text());
		
		$this->addColumnIfNotExists('aces', 'name', $this->string(), true);
		$this->addColumnIfNotExists('aces_history', 'name', $this->string(), true);
		
		$this->addColumnIfNotExists('acls', 'networks_id', $this->integer(), true);
		$this->addColumnIfNotExists('acls_history', 'networks_id', $this->integer(), true);
		
		$this->addColumnIfNotExists('acls', 'links', $this->text());
		$this->addColumnIfNotExists('acls_history', 'links', $this->text());
		
		$this->addColumnIfNotExists('access_types', 'ip_params_def', $this->string());
		$this->addColumnIfNotExists('access_in_aces', 'ip_params', $this->string());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('services_in_aces');
		$this->dropTableIfExists('networks_in_aces');
		
		$this->dropColumnIfExists('aces_history', 'services_ids');
		$this->dropColumnIfExists('aces_history', 'networks_ids');
		
		$this->dropColumnIfExists('acls', 'networks_id');
		$this->dropColumnIfExists('acls_history', 'networks_id');
		
		$this->dropColumnIfExists('acls', 'links');
		$this->dropColumnIfExists('acls_history', 'links');
		
		$this->dropColumnIfExists('aces', 'name');
		$this->dropColumnIfExists('aces_history', 'name');
		
		$this->dropColumnIfExists('access_types', 'ip_params_def');
		$this->dropColumnIfExists('access_in_aces', 'ip_params');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M240612053628AclExtend cannot be reverted.\n";

		return false;
	}
	*/
}
