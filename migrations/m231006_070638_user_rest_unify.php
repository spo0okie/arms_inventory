<?php

use yii\db\Migration;

/**
 * Class m231006_070638_user_rest_unify
 */
class m231006_070638_user_rest_unify extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('users','employee_id',$this->string(16)->null()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('users','employee_id',$this->string(16));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231006_070638_user_rest_unify cannot be reverted.\n";

        return false;
    }
    */
}
