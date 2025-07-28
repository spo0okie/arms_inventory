<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m231006_070638_user_rest_unify
 */
class m231006_070638_user_rest_unify extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->alterColumn('users', 'employee_id', $this->string(16)->null()->defaultValue(null));
		$this->alterColumn('users', 'Doljnost', $this->string()->null()->defaultValue(null));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('users', 'employee_id', $this->string(16));
		$this->alterColumn('users', 'Doljnost', $this->string());
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
