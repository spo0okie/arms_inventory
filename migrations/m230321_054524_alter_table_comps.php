<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230321_054524_alter_table_comps
 */
class m230321_054524_alter_table_comps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('comps','name',$this->string(128));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('comps','name',$this->string(32));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230321_054524_alter_table_comps cannot be reverted.\n";

        return false;
    }
    */
}
