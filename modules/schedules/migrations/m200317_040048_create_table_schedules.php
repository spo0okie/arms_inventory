<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m200317_040048_create_table_shedules
 */
class m200317_040048_create_table_schedules extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('{{%schedules}}'))) {
			$this->createTable('{{%schedules}}', [
				'[[id]]' => $this->primaryKey(),                //ключ
				'[[name]]' => $this->string(32),        //имя
				'[[description]]' => $this->string(255),        //описание
			], 'ENGINE=InnoDB');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('{{%schedules}}'))) $this->dropTable('{{%schedules}}');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m200317_040048_create_table_shedules cannot be reverted.\n";

		return false;
	}
	*/
}
