<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M241225123824MaintenanceDescr
 */
class M241225123824MaintenanceDescr extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('maintenance_jobs','description',$this->text());
		$this->alterColumn('maintenance_reqs','description',$this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('maintenance_jobs','description',$this->string(1024));
		$this->alterColumn('maintenance_reqs','description',$this->string(1024));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M241225123824MaintenanceDescr cannot be reverted.\n";

        return false;
    }
    */
}
