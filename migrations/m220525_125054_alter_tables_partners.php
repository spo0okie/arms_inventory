<?php

use yii\db\Migration;

/**
 * Class m220525_125054_alter_tables_partners
 */
class m220525_125054_alter_tables_partners extends Migration
{
    /**
     * {@inheritdoc}
     */
	public function safeUp()
	{
		$this->alterColumn('partners', 'inn', $this->bigInteger(12));
	}


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('partners', 'inn', $this->integer(10));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220525_125054_alter_tables_partners cannot be reverted.\n";

        return false;
    }
    */
}
