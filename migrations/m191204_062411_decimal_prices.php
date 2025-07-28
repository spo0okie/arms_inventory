<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавляем копейки к ценам
 * Class m191120_062411_float_prices
 */
class m191204_062411_decimal_prices extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->alterColumn('{{%contracts}}', 'total', $this->decimal(15, 2)->null());
		$this->alterColumn('{{%contracts}}', 'charge', $this->decimal(15, 2)->null());
		$this->alterColumn('{{%org_inet}}', 'cost', $this->decimal(15, 2)->null());
		$this->alterColumn('{{%org_inet}}', 'charge', $this->decimal(15, 2)->null());
		$this->alterColumn('{{%org_phones}}', 'cost', $this->decimal(15, 2)->null());
		$this->alterColumn('{{%org_phones}}', 'charge', $this->decimal(15, 2)->null());
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->alterColumn('{{%contracts}}', 'total', $this->float(2)->null());
		$this->alterColumn('{{%contracts}}', 'charge', $this->float(2)->null());
		$this->alterColumn('{{%org_inet}}', 'cost', $this->float(2)->null());
		$this->alterColumn('{{%org_inet}}', 'charge', $this->float(2)->null());
		$this->alterColumn('{{%org_phones}}', 'cost', $this->float(2)->null());
		$this->alterColumn('{{%org_phones}}', 'charge', $this->float(2)->null());
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
