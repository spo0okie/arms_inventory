<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210911_113706_alter_table_services
 */
class m210911_113706_alter_table_services extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('services');
		if (!isset($table->columns['cost']))
			$this->addColumn('services', 'cost', $this->float(2));
		
		if (!isset($table->columns['charge']))
			$this->addColumn('services', 'charge', $this->float(2));
		
		if (!isset($table->columns['partners_id'])) {
			$this->addColumn('services', 'partners_id', $this->integer()->null());
			$this->createIndex('idx-services_partners_id', 'services', 'partners_id');
		}
		
		if (!isset($table->columns['places_id'])) {
			$this->addColumn('services', 'places_id', $this->integer()->null());
			$this->createIndex('idx-services_places_id', 'services', 'places_id');
		}
		
		if (!isset($table->columns['archived'])) {
			$this->addColumn('services', 'archived', $this->integer()->notNull()->defaultValue(0));
			$this->createIndex('idx-services_archived', 'services', 'archived');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('services');
		if (isset($table->columns['cost']))
			$this->dropColumn('services', 'cost');
		
		if (isset($table->columns['partners_id'])) {
			$this->dropColumn('services', 'partners_id');
		}
		
		if (isset($table->columns['places_id'])) {
			$this->dropColumn('services', 'places_id');
		}
		
		if (isset($table->columns['archived'])) {
			$this->dropColumn('services', 'archived');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210911_113706_alter_table_services cannot be reverted.\n";

		return false;
	}
	*/
}
