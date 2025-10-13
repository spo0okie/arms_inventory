<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M251006102649SoftAddDescription extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->addColumnIfNotExists('soft','notepad',$this->text()->defaultValue(null)->after('comment'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('soft','notepad');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M251006102649SoftAddDescription cannot be reverted.\n";

        return false;
    }
    */
}
