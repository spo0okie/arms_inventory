<?php

use yii\db\Migration;

/**
 * Class m220819_132459_alter_table_net_domains
 */
class m220819_132459_alter_table_net_domains extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumn('net_domains','places_id',$this->integer()->null());
		$this->createIndex('net_domains-places-idx','net_domains','places_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('net_domains','places_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220819_132459_alter_table_net_domains cannot be reverted.\n";

        return false;
    }
    */
}
