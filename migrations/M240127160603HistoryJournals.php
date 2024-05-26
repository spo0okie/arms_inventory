<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240127160603HistoryJournals
 */
class M240127160603HistoryJournals extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->dropIndex('maintenance_reqs_history-updated_at','maintenance_reqs_history');
		$this->createIndex('maintenance_reqs_history-updated_at','maintenance_reqs_history','updated_at');
	
		$this->dropIndex('maintenance_jobs_history-updated_at','maintenance_jobs_history');
		$this->createIndex('maintenance_jobs_history-updated_at','maintenance_jobs_history','updated_at');
	
		$this->alterColumn('maintenance_reqs','updated_at',$this->timestamp());
		$this->alterColumn('maintenance_reqs_history','updated_at',$this->timestamp());
		$this->alterColumn('maintenance_jobs','updated_at',$this->timestamp());
		$this->alterColumn('maintenance_jobs_history','updated_at',$this->timestamp());
		
		$this->addColumnIfNotExists('maintenance_reqs_history','is_backup',$this->boolean());
	
		$this->addColumnIfNotExists('services','updated_at',$this->timestamp());
		$this->addColumnIfNotExists('services','updated_by',$this->string(32));
		
		$this->createTable('services_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'name'=>$this->string(64),
			'description'=>$this->text(),
			'search_text'=>$this->text(),
			'external_links'=>$this->text(),
			'is_end_user'=>$this->boolean(),
			'is_service'=>$this->boolean(),
			'archived'=>$this->boolean(),
			
			'cost'=>$this->float(),
			'charge'=>$this->float(),
			
			'links'=>$this->text(),
			'notebook'=>$this->text(),
			
			'weight'=>$this->integer(),
			'vm_cores'=>$this->integer(),
			'vm_ram'=>$this->integer(),
			'vm_hdd'=>$this->integer(),
			
			'responsible_id'=>$this->integer(),
			'infrastructure_user_id'=>$this->integer(),

			'providing_schedule_id'=>$this->integer(),
			'support_schedule_id'=>$this->integer(),

			'segment_id'=>$this->integer(),
			'parent_id'=>$this->integer(),
			'partners_id'=>$this->integer(),
			'places_id'=>$this->integer(),
			'currency_id'=>$this->integer(),

			'comps_ids'=>$this->text(),
			'techs_ids'=>$this->text(),
			'depends_ids'=>$this->text(),
			'support_ids'=>$this->text(),
			'contracts_ids'=>$this->text(),
			'infrastructure_support_ids'=>$this->text(),
			'maintenance_reqs_ids'=>$this->text(),

		]);
		$this->createIndex('services_history-master_id','services_history','master_id');
		$this->createIndex('services_history-updated_at','services_history','updated_at');
		$this->createIndex('services_history-updated_by','services_history','updated_by');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('services_history');

		$this->dropColumnIfExists('services','updated_at');
		$this->dropColumnIfExists('services','updated_by');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240127160603HistoryJournals cannot be reverted.\n";

        return false;
    }
    */
}
