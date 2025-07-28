<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240725041322CleanUnused
 */
class M240725041322CleanUnused extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('services_history', 'aces_ids', $this->text());
		$this->dropTableIfExists('service_connections');
		$this->dropTableIfExists('comps_in_targets');
		$this->dropTableIfExists('comps_in_initiators');
		$this->dropTableIfExists('techs_in_targets');
		$this->dropTableIfExists('techs_in_initiators');
		$this->dropTableIfExists('arms');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('services_history', 'aces_ids');
	}
	
	
}
