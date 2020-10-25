<?php

use yii\db\Migration;

/**
 * Class m201024_153753_add_tech_specs
 */
class m201024_153753_add_tech_specs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('arms');
		if (!isset($table->columns['specs'])) {
			$this->addColumn('arms','specs',$this->text());
		}
	
		$table=$this->db->getTableSchema('techs');
		if (!isset($table->columns['specs'])) {
			$this->addColumn('techs','specs',$this->text());
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('arms');
		if (isset($table->columns['specs'])) $this->dropColumn('arms','specs');

		$table=$this->db->getTableSchema('techs');
		if (isset($table->columns['specs'])) $this->dropColumn('techs','specs');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201024_153753_add_tech_specs cannot be reverted.\n";

        return false;
    }
    */
}
