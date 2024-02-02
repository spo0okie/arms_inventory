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
		$this->addColumnIfNotExist('maintenance_reqs','archived',$this->boolean(),true);
		$this->addColumnIfNotExist('maintenance_reqs_history','archived',$this->boolean());
		$this->addColumnIfNotExist('maintenance_jobs','archived',$this->boolean(),true);
		$this->addColumnIfNotExist('maintenance_jobs_history','archived',$this->boolean());
		$this->addColumnIfNotExist('attaches','maintenance_reqs_id',$this->integer(),true);
		$this->addColumnIfNotExist('attaches','maintenance_jobs_id',$this->integer(),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('maintenance_reqs','archived');
		$this->dropColumnIfExist('maintenance_reqs_history','archived');
		$this->dropColumnIfExist('maintenance_jobs','archived');
		$this->dropColumnIfExist('maintenance_jobs_history','archived');
		$this->dropColumnIfExist('attaches','maintenance_reqs_id');
		$this->dropColumnIfExist('attaches','maintenance_jobs_id');
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
