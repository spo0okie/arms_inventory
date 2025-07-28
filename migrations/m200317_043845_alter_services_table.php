<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m200317_033845_alter_services_table
 */
class m200317_043845_alter_services_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('services');
		
		if (!isset($table->columns['responsible_id'])) {
			$this->addColumn('{{%services}}', '[[responsible_id]]', $this->integer()->null());
			$this->createIndex('{{%idx-services_responsible}}', '{{%services}}', '[[responsible_id]]');
			// add foreign key for table `{{%department}}`
			$this->addForeignKey(
				'{{%fk-services-responsible}}',
				'{{%services}}',
				'[[responsible_id]]',
				'{{%users}}',
				'id',
				'RESTRICT'
			);
		}
		
		if (!isset($table->columns['providing_schedule_id'])) {
			$this->addColumn('{{%services}}', '[[providing_schedule_id]]', $this->integer()->null());
			$this->createIndex('{{%idx-services_providing_schedule}}', '{{%services}}', '[[providing_schedule_id]]');
			$this->addForeignKey(
				'{{%fk-services-providing_schedule}}',
				'{{%services}}',
				'[[providing_schedule_id]]',
				'{{%schedules}}',
				'id',
				'RESTRICT'
			);
		}
		
		if (!isset($table->columns['support_schedule_id'])) {
			$this->addColumn('{{%services}}', '[[support_schedule_id]]', $this->integer()->null());
			$this->createIndex('{{%idx-services_support_schedule}}', '{{%services}}', '[[support_schedule_id]]');
			$this->addForeignKey(
				'{{%fk-services-support_schedule}}',
				'{{%services}}',
				'[[support_schedule_id]]',
				'{{%schedules}}',
				'id',
				'RESTRICT'
			);
		}
		
		
		if (isset($table->columns['user_group_id']))
			$this->dropColumn('{{%services}}', '[[user_group_id]]');
		
		if (isset($table->columns['sla_id']))
			$this->dropColumn('{{%services}}', '[[sla_id]]');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('services');
		
		if (isset($table->foreignKeys['fk-services-responsible'])) $this->dropForeignKey('fk-services-responsible', '{{%services}}');
		if (isset($table->foreignKeys['fk-services-support_schedule'])) $this->dropForeignKey('fk-services-support_schedule', '{{%services}}');
		if (isset($table->foreignKeys['fk-services-providing_schedule'])) $this->dropForeignKey('fk-services-providing_schedule', '{{%services}}');
		
		if (isset($table->columns['responsible_id'])) $this->dropColumn('{{%services}}', '[[responsible_id]]');
		if (isset($table->columns['providing_schedule_id'])) $this->dropColumn('{{%services}}', '[[providing_schedule_id]]');
		if (isset($table->columns['support_schedule_id'])) $this->dropColumn('{{%services}}', '[[support_schedule_id]]');
		
		if (!isset($table->columns['user_group_id'])) {
			$this->addColumn('{{%services}}', '[[user_group_id]]', $this->integer()->null());
			$this->createIndex('{{%idx-idx-services-group_id}}', '{{%services}}', '[[user_group_id]]');
		}
		
		if (!isset($table->columns['sla_id'])) {
			$this->addColumn('{{%services}}', '[[sla_id]]', $this->integer()->null());
			$this->createIndex('{{%idx-services-sal_id}}', '{{%services}}', '[[sla_id]]');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m200317_033845_alter_services_table cannot be reverted.\n";

		return false;
	}
	*/
}
