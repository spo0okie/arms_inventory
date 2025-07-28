<?php
namespace app\migrations;
use app\models\Currency;
use yii\db\Migration;

/**
 * Class m210921_035506_create_table_currency
 */
class m210921_035506_create_table_currency extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('currency'))) {
			$this->createTable('currency', [
				'[[id]]' => $this->primaryKey(),
				'[[symbol]]' => $this->string(12),
				'[[code]]' => $this->string(64),
				'[[name]]' => $this->string(128),
				'[[comment]]' => $this->string(255),
				'[[notepad]]' => $this->text(),
			]);
			
			$RUR = new Currency();
			$RUR->id = 1;
			$RUR->name = 'Российский рубль';
			$RUR->code = 'RUR';
			$RUR->symbol = '₽';
			$RUR->comment = 'Валюта по умолчанию';
			$RUR->save();
			
			$USD = new Currency();
			$USD->id = 2;
			$USD->name = 'Доллар США';
			$USD->code = 'USD';
			$USD->symbol = '$';
			$USD->save();
			
			$EUR = new Currency();
			$EUR->id = 3;
			$EUR->name = 'Евро';
			$EUR->code = 'EUR';
			$EUR->symbol = '€';
			$EUR->save();
			
		}
		
		if (!is_null($table = $this->db->getTableSchema('contracts'))) {
			if (!isset($table->columns['currency_id'])) {
				$this->addColumn('contracts', 'currency_id', $this->integer()->notNull()->defaultValue(1));
				$this->createIndex('idx-contracts-currency_id', 'contracts', 'currency_id');
			}
			
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('currency'))) {
			$this->dropTable('currency');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210921_035506_create_table_currency cannot be reverted.\n";

		return false;
	}
	*/
}
