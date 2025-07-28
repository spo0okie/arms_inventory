<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_173041_fix_many_2_many
 */
class m191219_100002_fix_contracts_in_materials_id extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('{{%contracts_in_materials}}');
		if (!$table->getColumn('id')->autoIncrement) {
			if (!count($table->primaryKey)) {
				$this->addPrimaryKey('id', 'contracts_in_materials', 'id');
			}
			$this->alterColumn('contracts_in_materials', 'id', $this->integer()->notNull()->append('AUTO_INCREMENT'));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('contracts_in_materials', 'id', $this->integer()->notNull());
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
