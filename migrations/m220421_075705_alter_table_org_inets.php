<?php

use yii\db\Migration;

/**
 * Class m220421_075705_alter_table_org_inets
 */
class m220421_075705_alter_table_org_inets extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('org_inet', 'prov_tel_id', $this->integer()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('org_inet', 'prov_tel_id', $this->integer());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220421_075705_alter_table_org_inets cannot be reverted.\n";

        return false;
    }
    */
}
