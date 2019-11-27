<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%org_phones}}`.
 */
class m191120_095815_add_cost_column_to_org_phones_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	    $table=$this->db->getTableSchema('{{%org_phones}}');
	
	    if (!isset($table->columns['cost']))
		    $this->addColumn('{{%org_phones}}','cost',$this->float(2)->null());
	    if (!isset($table->columns['charge']))
		    $this->addColumn('{{%org_phones}}','charge',$this->float(2)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
	    $table=$this->db->getTableSchema('{{%org_phones}}');
	
	    if (isset($table->columns['cost']))
		    $this->dropColumn('{{%org_phones}}','cost');
	    if (isset($table->columns['charge']))
		    $this->dropColumn('{{%org_phones}}','charge');
    }
}
