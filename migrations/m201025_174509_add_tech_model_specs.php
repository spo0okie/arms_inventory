<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m201025_174509_add_tech_model_specs
 */
class m201025_174509_add_tech_model_specs extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('tech_models');
		if (!isset($table->columns['individual_specs'])) {
			$this->addColumn('tech_models', 'individual_specs', $this->integer(1)->defaultValue(0));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('tech_models');
		if (isset($table->columns['individual_specs'])) {
			$this->dropColumn('tech_models', 'individual_specs');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m201025_174509_add_tech_model_specs cannot be reverted.\n";

		return false;
	}
	*/
}
