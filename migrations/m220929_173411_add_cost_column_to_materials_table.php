<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%materials}}`.
 */
class m220929_173411_add_cost_column_to_materials_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$table = $this->db->getTableSchema('materials');
		if (!isset($table->columns['cost']))
			$this->addColumn('materials','cost',$this->float(2));
		
		if (!isset($table->columns['charge']))
			$this->addColumn('materials','charge',$this->float(2));
		
		if (!isset($table->columns['currency_id'])){
			$this->addColumn('materials','currency_id',$this->integer()->notNull()->defaultValue(1));
			$this->createIndex('idx-materials-currency_id','materials','currency_id');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$table=$this->db->getTableSchema('materials');
		if (isset($table->columns['cost']))
			$this->dropColumn('materials','cost',$this->float(2));
		
		if (isset($table->columns['charge']))
			$this->dropColumn('materials','charge',$this->float(2));
		
		if (isset($table->columns['currency_id']))
			$this->dropColumn('materials','currency_id',$this->integer()->notNull()->defaultValue(1));
		
	}
}
