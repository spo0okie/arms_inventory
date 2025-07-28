<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m191208_173041_fix_users_id extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('{{%users}}');
		if (!$table->getColumn('id')->autoIncrement) {
			if (!count($table->primaryKey)) {
				$this->addPrimaryKey('id', 'users', 'id');
			}
			$this->alterColumn('users', 'id', $this->integer()->notNull()->append('AUTO_INCREMENT'));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('users', 'id', $this->integer()->notNull());
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m191208_173041_fix_many_2_many cannot be reverted.\n";

		return false;
	}
	*/
}
