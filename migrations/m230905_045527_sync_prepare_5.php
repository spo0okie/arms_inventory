<?php

use yii\db\Migration;

/**
 * Class m230905_045527_sync_prepare_5
 */
class m230905_045527_sync_prepare_5 extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('soft','manufacturers_id',$this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('soft','manufacturers_id',$this->integer());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230905_045527_sync_prepare_5 cannot be reverted.\n";

        return false;
    }
    */
}
