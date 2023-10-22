<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавляет стоимость к услугам
 * Handles adding columns to table `{{%org_inet}}`.
 */
class m191119_145841_add_cost_column_to_org_inet_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $this->addColumn('{{%org_inet}}','cost',$this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->dropColumn('{{%org_inet}}','cost');
    }
}
