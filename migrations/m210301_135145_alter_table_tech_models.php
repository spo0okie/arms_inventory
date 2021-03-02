<?php

use yii\db\Migration;

/**
 * Class m210301_135145_alter_table_tech_models
 */
class m210301_135145_alter_table_tech_models extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('tech_models');
		if (!isset($table->columns['ports'])) {
			$this->addColumn('tech_models','ports',$this->text()->Null());
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('tech_models');
		if (isset($table->columns['ports'])) {
			$this->dropColumn('tech_models','ports');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210301_135145_alter_table_tech_models cannot be reverted.\n";

        return false;
    }
    */
}
