<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240129130314HistoryJournalsTechs
 */
class M240129130314HistoryJournalsTechs extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('techs','updated_at',$this->timestamp());
		$this->addColumnIfNotExists('techs','updated_by',$this->string(32));
		
		$this->createTable('techs_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'num'=>$this->string(16),
			'inv_num'=>$this->string(128),
			'sn'=>$this->string(128),
			'uid'=>$this->string(16),
			'hostname'=>$this->string(128),

			'domain_id'=>$this->integer(),
			
			'model_id'=>$this->integer(),
			'arms_id'=>$this->integer(),
			'installed_id'=>$this->integer(),

			'places_id'=>$this->integer(),

			'user_id'=>$this->integer(),
			'head_id'=>$this->integer(),
			'responsible_id'=>$this->integer(),
			'it_staff_id'=>$this->integer(),

			'state_id'=>$this->integer(),
			'scans_id'=>$this->integer(),
			'departments_id'=>$this->integer(),
			'comp_id'=>$this->integer(),

			'partners_id'=>$this->integer(),

			
			'ip'=>$this->string(512),
			'mac'=>$this->string(255),
			'installed_pos'=>$this->string(128),
			'installed_pos_end'=>$this->string(128),
			
			'url'=>$this->text(),
			'comment'=>$this->text(),
			'history'=>$this->text(),
			'specs'=>$this->text(),
			'hw'=>$this->text(),
			'external_links'=>$this->text(),
			
			'installed_back'=>$this->boolean(),
			'full_length'=>$this->boolean(),
			
			'contracts_ids' => $this->text(),
			'services_ids' => $this->text(),
			'lic_items_ids' => $this->text(),
			'lic_keys_ids' => $this->text(),
			'lic_groups_ids' => $this->text(),
			'maintenance_reqs_ids' => $this->text(),
		
		]);
		$this->createIndex('techs_history-master_id','techs_history','master_id');
		$this->createIndex('techs_history-updated_at','techs_history','updated_at');
		$this->createIndex('techs_history-updated_by','techs_history','updated_by');
		
		$this->createTable('tech_models_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'type_id'=>$this->integer(),
			'manufacturers_id'=>$this->integer(),
			'scans_id'=>$this->integer(),
			
			'individual_specs'=>$this->boolean(),
			'contain_front_rack'=>$this->boolean(),
			'contain_back_rack'=>$this->boolean(),
			'front_rack_two_sided'=>$this->boolean(),
			'back_rack_two_sided'=>$this->boolean(),
			'archived'=>$this->boolean(),
			
			'name'=>$this->string(128),
			'short'=>$this->string(24),

			'links'=>$this->text(),
			'comment'=>$this->text(),
			'ports'=>$this->text(),
			'front_rack_layout'=>$this->text(),
			'back_rack_layout'=>$this->text(),
		]);
		$this->createIndex('tech_models_history-master_id','tech_models_history','master_id');
		$this->createIndex('tech_models_history-updated_at','tech_models_history','updated_at');
		$this->createIndex('tech_models_history-updated_by','tech_models_history','updated_by');
		
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumnIfExists('techs','updated_by');
	
		$this->dropTable('techs_history');
		$this->dropTable('tech_models_history');
	
		
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240129130314HistoryJournalsTechs cannot be reverted.\n";

        return false;
    }
    */
}
