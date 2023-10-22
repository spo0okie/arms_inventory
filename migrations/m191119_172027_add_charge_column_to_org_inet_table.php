<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Handles adding columns to table `{{%org_inet}}`.
 */
class m191119_172027_add_charge_column_to_org_inet_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $this->addColumn('{{%org_inet}}','charge',$this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->dropColumn('{{%org_inet}}','charge');
    }
}
