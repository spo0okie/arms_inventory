<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240328034135CompsHistory
 */
class M240328034135CompsHistory extends ArmsMigration
{

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumnIfNotExist('comps','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExist('comps','updated_by',$this->string(32),true);
		
		$this->createTable('comps_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'arm_id'=>$this->integer(),
			'domain_id'=>$this->integer(),
			'name'=>$this->string(128),
			'os'=>$this->string(128),
			'raw_hw'=>$this->text(),
			'raw_soft'=>$this->text(),
			'raw_version'=>$this->string(32),
			'ip'=>$this->text(),
			'mac'=>$this->text(),
			'ip_ignore'=>$this->text(),
			'user_id'=>$this->integer(),
			'external_links'=>$this->text(),
			'archived'=>$this->boolean(),
			'services_ids'=>$this->text(),
			'aces_ids'=>$this->text(),
			'acls_ids'=>$this->text(),
			'lic_groups_ids' => $this->text(),
			'lic_items_ids' => $this->text(),
			'lic_keys_ids' => $this->text(),
			'maintenance_reqs_ids'=>$this->text(),
			'maintenance_jobs_ids'=>$this->text(),
		]);
		$this->createIndex('comps_history-master_id','comps_history','master_id');
		$this->createIndex('comps_history-updated_at','comps_history','updated_at');
		$this->createIndex('comps_history-updated_by','comps_history','updated_by');
		
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumnIfExist('comps','updated_by');
		$this->dropTable('comps_history');
	}
	
}
