<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210214_154227_table_net_domains
 */
class m210214_154227_table_net_domains extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('net_domains'))) {
			$this->createTable('net_domains', [
				'[[id]]' => $this->primaryKey(),        //ключ
				'[[name]]' => $this->string(),            //наименование
				'[[comment]]' => $this->text(),            //комментарий
			], 'ENGINE=InnoDB');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('{{%net_domains}}'))) $this->dropTable('{{%net_domains}}');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210214_154227_table_net_domains cannot be reverted.\n";

		return false;
	}
	*/
}
