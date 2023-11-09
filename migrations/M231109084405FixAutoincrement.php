<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Class M231109084405FixAutoincrement
 */
class M231109084405FixAutoincrement extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('hw_ignore','id',$this->integer()->append(' AUTO_INCREMENT'));
		$this->alterColumn('tech_states','id',$this->integer()->append(' AUTO_INCREMENT'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M231109084405FixAutoincrement no need to be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M231109084405FixAutoincrement cannot be reverted.\n";

        return false;
    }
    */
}
