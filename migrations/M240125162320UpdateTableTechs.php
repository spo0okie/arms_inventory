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
    public function safeUp()
    {
		$this->addColumnIfNotExist('techs','domain_id',$this->integer(),true);
		$this->addColumnIfNotExist('techs','hostname',$this->string(128),true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('techs','domain_id');
		$this->dropColumnIfExist('techs','hostname');
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
