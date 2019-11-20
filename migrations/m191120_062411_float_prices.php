<?php

use yii\db\Migration;

/**
 * Добавляем копейки к ценам
 * Class m191120_062411_float_prices
 */
class m191120_062411_float_prices extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $this->alterColumn('{{%contracts}}','total',$this->float(2)->null());
	    $this->alterColumn('{{%contracts}}','charge',$this->float(2)->null());
	    $this->alterColumn('{{%org_inet}}','cost',$this->float(2)->null());
	    $this->alterColumn('{{%org_inet}}','charge',$this->float(2)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->alterColumn('{{%contracts}}','total',$this->integer()->null());
	    $this->alterColumn('{{%contracts}}','charge',$this->integer()->null());
	    $this->alterColumn('{{%org_inet}}','cost',$this->integer()->null());
	    $this->alterColumn('{{%org_inet}}','charge',$this->integer()->null());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191120_062411_float_prices cannot be reverted.\n";

        return false;
    }
    */
}
