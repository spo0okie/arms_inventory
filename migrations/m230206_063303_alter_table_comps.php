<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230206_063303_alter_table_comps
 */
class m230206_063303_alter_table_comps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('comps','comment',$this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('comps','comment',$this->string(128));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230206_063303_alter_table_comps cannot be reverted.\n";

        return false;
    }
    */
}
