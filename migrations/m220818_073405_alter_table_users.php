<?php

use yii\db\Migration;

/**
 * Class m220818_073405_alter_table_users
 */
class m220818_073405_alter_table_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->createIndex('orgStruct-org-index','org_struct','org_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropIndex('orgStruct-org-index','org_struct');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220818_073405_alter_table_users cannot be reverted.\n";

        return false;
    }
    */
}
