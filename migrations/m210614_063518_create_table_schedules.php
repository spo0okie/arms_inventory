<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210614_063518_create_table_schedules
 */
class m210614_063518_create_table_schedules extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('schedules_entries'))) {
			$this->createTable('schedules_entries', [
				'[[id]]' => $this->primaryKey(),
				'[[schedule_id]]' => $this->integer(),
				
				'[[date]]' => $this->string(64),
				'[[schedule]]' => $this->string(64),
				'[[date_end]]' => $this->string(64),
				
				'[[is_period]]' => $this->boolean(),
				'[[is_work]]' => $this->boolean(),
				
				'[[comment]]' => $this->string(255),
				'[[history]]' => $this->text(),
				'[[created_at]]' => $this->timestamp(),
				//'[[updated_at]]'	=> $this->timestamp()
			], 'DEFAULT CHARSET=utf8');
			
			$this->createIndex('{{%idx-schedules_days_org_id}}', '{{%schedules_entries}}', '[[schedule_id]]');
			$this->createIndex('{{%idx-schedules_days_date}}', '{{%schedules_entries}}', '[[date]]');
			$this->createIndex('{{%idx-schedules_days_end_date}}', '{{%schedules_entries}}', '[[date_end]]');
			$this->createIndex('{{%idx-schedules_days_is_period}}', '{{%schedules_entries}}', '[[is_period]]');
			$this->createIndex('{{%idx-schedules_days_is_work}}', '{{%schedules_entries}}', '[[is_work]]');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('schedules_entries'))) $this->dropTable('{{%schedules_entries}}');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210614_063518_create_table_schedules cannot be reverted.\n";

		return false;
	}
	*/
}
