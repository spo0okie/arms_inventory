<?php

use yii\db\Migration;

/**
 * Class m220327_073551_alter_table_comps
 */
class m220327_073551_alter_table_comps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table = $this->db->getTableSchema('comps');
		if (!isset($table->columns['mac']))
			$this->addColumn('comps', 'mac', $this->string(512));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table = $this->db->getTableSchema('comps');
		if (isset($table->columns['mac']))
			$this->dropColumn('comps', 'mac');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220327_073551_alter_table_comps cannot be reverted.\n";

        return false;
    }
    */
}
