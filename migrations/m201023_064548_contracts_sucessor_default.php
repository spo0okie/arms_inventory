<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m201023_064548_contracts_sucessor_default
 */
class m201023_064548_contracts_sucessor_default extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('contracts','is_successor',$this->integer(1)->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('contracts','is_successor',$this->integer(1));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201023_064548_contracts_sucessor_default cannot be reverted.\n";

        return false;
    }
    */
}
