<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240201144730MaintenanceUpdate
 */
class M240201144730MaintenanceUpdate extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('maintenance_reqs','archived',$this->boolean(),true);
		$this->addColumnIfNotExists('maintenance_reqs_history','archived',$this->boolean());
		$this->addColumnIfNotExists('maintenance_jobs','archived',$this->boolean(),true);
		$this->addColumnIfNotExists('maintenance_jobs_history','archived',$this->boolean());
		$this->addColumnIfNotExists('attaches','maintenance_reqs_id',$this->integer(),true);
		$this->addColumnIfNotExists('attaches','maintenance_jobs_id',$this->integer(),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('maintenance_reqs','archived');
		$this->dropColumnIfExists('maintenance_reqs_history','archived');
		$this->dropColumnIfExists('maintenance_jobs','archived');
		$this->dropColumnIfExists('maintenance_jobs_history','archived');
		$this->dropColumnIfExists('attaches','maintenance_reqs_id');
		$this->dropColumnIfExists('attaches','maintenance_jobs_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240201144730MaintenanceUpdate cannot be reverted.\n";

        return false;
    }
    */
}
