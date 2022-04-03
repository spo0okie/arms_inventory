<?php

use yii\db\Migration;

/**
 * Class m220402_185406_alter_table_schedules
 */
class m220402_185406_alter_table_schedules extends Migration
{
    /**
     * {@inheritdoc}
     */
	public function safeUp()
	{
		$table = $this->db->getTableSchema('schedules');
		if (!isset($table->columns['start_date']))
			$this->addColumn('schedules', 'start_date', $this->string(64));
		if (!isset($table->columns['end_date']))
			$this->addColumn('schedules', 'end_date', $this->string(64));
		if (!isset($table->columns['override_id']))
			$this->addColumn('schedules', 'override_id', $this->integer()->null());
		
		if (is_null($table = $this->db->getTableSchema('users_in_schedules_entries'))) {
			$this->createTable('users_in_schedules_entries', [
				'[[id]]'					=> $this->primaryKey(),		//ключ
				'[[users_id]]'				=> $this->integer(),		//users
				'[[schedules_entries_id]]'	=> $this->integer(),		//schedule entry
			],'ENGINE=InnoDB');
			
			$this->createIndex('{{%idx-users_in_schedules_entries-users}}', 	'{{%users_in_schedules_entries}}', '[[users_id]]');
			$this->createIndex('{{%idx-users_in_schedules_entries-entries}}', '{{%users_in_schedules_entries}}', '[[schedules_entries_id]]');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$table = $this->db->getTableSchema('schedules');
		if (isset($table->columns['start_date']))
			$this->dropColumn('schedules', 'start_date');
		if (isset($table->columns['end_date']))
			$this->dropColumn('schedules', 'end_date');
		if (isset($table->columns['override_id']))
			$this->dropColumn('schedules', 'override_id');
		
		$table = $this->db->getTableSchema('schedules_entries');
		if (isset($table->columns['users_id']))
			$this->dropColumn('schedules_entries', 'users_id');
		
		if (!is_null($table = $this->db->getTableSchema('users_in_schedules_entries'))) {
			$this->dropTable('users_in_schedules_entries');
		}
	}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220402_185406_alter_table_schedules cannot be reverted.\n";

        return false;
    }
    */
}
