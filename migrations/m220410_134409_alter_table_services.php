<?php

use yii\db\Migration;

/**
 * Class m220410_134409_alter_table_services
 */
class m220410_134409_alter_table_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table = $this->db->getTableSchema('services');
		if (!isset($table->columns['search_text']))
			$this->addColumn('services', 'search_text', $this->string(255));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table = $this->db->getTableSchema('services');
		if (isset($table->columns['search_text']))
			$this->dropColumn('services', 'search_text');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220410_134409_alter_table_services cannot be reverted.\n";

        return false;
    }
    */
}
