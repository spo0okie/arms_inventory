<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m191208_164401_add_default_ip_values_in_comps
 */
class m191208_164401_add_default_ip_values_in_comps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $this->alterColumn('comps','ip',$this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn('comps','ip',$this->string(255)->notNull());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191208_164401_add_default_ip_values_in_comps cannot be reverted.\n";

        return false;
    }
    */
}
