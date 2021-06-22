<?php

use yii\db\Migration;

/**
 * Class m210621_131426_alter_table_services
 */
class m210621_131426_alter_table_services extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$table = $this->db->getTableSchema('services');
		if (!isset($table->columns['parent_id'])) {
			$this->addColumn('services', 'parent_id', $this->integer()->null());
			$this->createIndex('{{%idx-services_parent_id}}', '{{%services}}', '[[parent_id]]');
			
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$table = $this->db->getTableSchema('services');
		if (isset($table->columns['parent_id'])) {
			$this->dropColumn('services', 'parent_id');
			$this->dropIndex('{{%idx-services_parent_id}}', '{{%services}}');
		}
	}
}

