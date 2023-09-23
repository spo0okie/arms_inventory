<?php

use yii\db\Migration;

/**
 * Class m230923_092107_user_sync_prepare
 */
class m230923_092107_user_sync_prepare extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('users','Orgeh',$this->string(16)->defaultValue(null));
		$this->alterColumn('users','org_id',$this->integer()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('users','Orgeh',$this->string(16));
		$this->alterColumn('users','org_id',$this->integer());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230923_092107_user_sync_prepare cannot be reverted.\n";

        return false;
    }
    */
}
