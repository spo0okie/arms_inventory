<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m200525_200810_create_table_techs_in_services
 */
class m200525_200810_create_table_techs_in_services extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('techs_in_services'))) {
			$this->createTable('techs_in_services', [
				'[[id]]' => $this->primaryKey(),        //ключ
				'[[service_id]]' => $this->integer(),        //сервиса
				'[[tech_id]]' => $this->integer(),        //оборудование
			], 'ENGINE=InnoDB');
			
			$this->createIndex('{{%idx-techs_in_services_uid}}', '{{%techs_in_services}}', '[[tech_id]]');
			$this->createIndex('{{%idx-techs_in_services_sid}}', '{{%techs_in_services}}', '[[service_id]]');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('{{%techs_in_services}}'))) $this->dropTable('{{%techs_in_services}}');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m200525_200810_create_table_techs_in_services cannot be reverted.\n";

		return false;
	}
	*/
}
