<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M250224152754TechsSupportService
 */
class M250224152754TechsSupportService extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('techs','management_service_id',$this->integer(),true);
		$this->addColumnIfNotExists('techs_history','management_service_id',$this->integer(),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('techs','management_service_id');
		$this->dropColumnIfExists('techs_history','management_service_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250224152754TechsSupportService cannot be reverted.\n";

        return false;
    }
    */
}
