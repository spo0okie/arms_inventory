<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Handles adding columns to table `{{%contracts}}`.
 */
class m191119_172409_add_charge_column_to_contracts_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumn('{{%contracts}}','charge',$this->integer()->null());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumn('{{%contracts}}','charge');
	}

}
