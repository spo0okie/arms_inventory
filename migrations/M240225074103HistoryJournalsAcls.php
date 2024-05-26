<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240225074103HistoryJournalsAcls
 */
class M240225074103HistoryJournalsAcls extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('aces','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('aces','updated_by',$this->string(32),true);
	
		$this->addColumnIfNotExists('acls','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('acls','updated_by',$this->string(32),true);
	
		$this->addColumnIfNotExists('schedules','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('schedules','updated_by',$this->string(32),true);
		$this->dropColumnIfExists('schedules','created_at');
	
		$this->addColumnIfNotExists('schedules_entries','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('schedules_entries','updated_by',$this->string(32),true);
		$this->dropColumnIfExists('schedules_entries','created_at');
	
		$this->createTable('aces_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'comment'=>$this->string(),
			'notepad'=>$this->text(),
			'acls_id'=>$this->integer(),
			'users_ids'=>$this->text(),
			'comps_ids'=>$this->text(),
			'access_types_ids'=>$this->text(),
			'ips'=>$this->text(),
		]);
		$this->createIndex('aces_history-master_id','aces_history','master_id');
		$this->createIndex('aces_history-updated_at','aces_history','updated_at');
		$this->createIndex('aces_history-updated_by','aces_history','updated_by');
	
	
		$this->createTable('acls_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'comment'=>$this->string(),
			'notepad'=>$this->text(),
			'schedules_id'=>$this->integer(),
			'services_id'=>$this->integer(),
			'ips_id'=>$this->integer(),
			'comps_id'=>$this->integer(),
			'techs_id'=>$this->integer(),
			'aces_ids'=>$this->text(),
		]);
		$this->createIndex('acls_history-master_id','acls_history','master_id');
		$this->createIndex('acls_history-updated_at','acls_history','updated_at');
		$this->createIndex('acls_history-updated_by','acls_history','updated_by');
	
		$this->createTable('schedules_entries_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'comment'=>$this->string(),
			'notepad'=>$this->text(),
			'schedule_id'=>$this->integer(),
		
			'date'=>$this->string(64),
			'date_end'=>$this->string(64),
			'schedule'=>$this->string(),
		
			'is_period'=>$this->boolean(),
			'is_work'=>$this->boolean(),
		]);
		$this->createIndex('schedules_entries_history-master_id','schedules_entries_history','master_id');
		$this->createIndex('schedules_entries_history-updated_at','schedules_entries_history','updated_at');
		$this->createIndex('schedules_entries_history-updated_by','schedules_entries_history','updated_by');
	
		$this->createTable('schedules_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'name'=>$this->string(),
			'description'=>$this->string(),
			'history'=>$this->text(),

			'parent_id'=>$this->integer(),
			'override_id'=>$this->integer(),
		
			'start_date'=>$this->string(64),
			'end_date'=>$this->string(64),
			
			'entries_ids'=>$this->text(),
			'providing_services_ids' => $this->text(),
			'support_services_ids' => $this->text(),
			'acls_ids' => $this->text(),
			'maintenance_jobs_ids' => $this->text(),
			'overrides_ids' => $this->text(),
		
		]);
		$this->createIndex('schedules_history-master_id','schedules_history','master_id');
		$this->createIndex('schedules_history-updated_at','schedules_history','updated_at');
		$this->createIndex('schedules_history-updated_by','schedules_history','updated_by');
	
		$this->addColumnIfNotExists('services_history','acls_ids',$this->text());
		$this->addColumnIfNotExists('services_history','maintenance_jobs_ids',$this->text());

		$this->addColumnIfNotExists('techs_history','acls_ids',$this->text());
		$this->addColumnIfNotExists('techs_history','maintenance_jobs_ids',$this->text());
		$this->addColumnIfNotExists('techs_history','archived',$this->boolean());
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('services_history','acls_ids');
		$this->dropColumnIfExists('services_history','maintenance_jobs_ids');
		$this->dropColumnIfExists('techs_history','acls_ids');
		$this->dropColumnIfExists('techs_history','archived');
		$this->dropColumnIfExists('techs_history','maintenance_jobs_ids');

		$this->dropColumnIfExists('aces','updated_at');
		$this->dropColumnIfExists('aces','updated_by');
		$this->dropColumnIfExists('acls','updated_at');
		$this->dropColumnIfExists('acls','updated_by');
	
		$this->dropTable('aces_history');
		$this->dropTable('acls_history');
		$this->dropTable('schedules_history');
		$this->dropTable('schedules_entries_history');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240225074103HistoryJournalsAcls cannot be reverted.\n";

        return false;
    }
    */
}
