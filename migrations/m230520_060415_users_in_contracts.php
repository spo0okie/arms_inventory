<?php

use yii\db\Migration;

/**
 * Class m230520_060415_users_in_contracts
 */
class m230520_060415_users_in_contracts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('users_in_contracts'))) {
			$this->createTable('users_in_contracts', [
				'id' => $this->primaryKey(),
				'users_id' => $this->integer()->notNull(),
				'contracts_id' => $this->integer()->notNull(),
			]);
			
			$this->createIndex('idx-users_in_contracts-users_id','users_in_contracts','users_id');
			$this->createIndex('idx-users_in_contracts-contracts_id','users_in_contracts','contracts_id');
		}

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table = $this->db->getTableSchema('users_in_contracts'))) {
			$this->dropTable('users_in_contracts');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230520_060415_users_in_contracts cannot be reverted.\n";

        return false;
    }
    */
}
