<?php

use yii\db\Migration;

/**
 * Class m210612_143410_alter_techs_table
 */
class m210612_143410_alter_techs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('techs', 'ip', $this->string(255));
		$this->alterColumn('techs', 'mac', $this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('techs', 'ip', $this->string(16));
		$this->alterColumn('techs', 'mac', $this->string(17));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210612_143410_alter_techs_table cannot be reverted.\n";

        return false;
    }
    */
}
