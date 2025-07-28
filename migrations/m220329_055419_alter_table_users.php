<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m220329_055419_alter_table_users
 */
class m220329_055419_alter_table_users extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('users');
		if (!isset($table->columns['private_phone']))
			$this->addColumn('users', 'private_phone', $this->string(255));
		$this->alterColumn('users', 'Mobile', $this->string(255));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('users');
		if (isset($table->columns['private_phone']))
			$this->dropColumn('users', 'private_phone');
		$this->alterColumn('users', 'Mobile', $this->string(128));
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m220329_055419_alter_table_users cannot be reverted.\n";

		return false;
	}
	*/
}
