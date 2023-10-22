<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Handles adding columns to table `{{%contracts}}`.
 */
class m191106_115822_add_total_column_to_contracts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$this->addColumn('{{%contracts}}','total',$this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $this->dropColumn('{{%contracts}}','total');
    }
}
