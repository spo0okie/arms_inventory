<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

class M250828100718AdditionalHistory extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->execute("ALTER TABLE `lic_keys` ENGINE=InnoDB\n");
		
		$this->addColumnIfNotExists('segments', 'updated_at', $this->timestamp(), true);
		$this->addColumnIfNotExists('segments', 'updated_by', $this->string(32), true);
		
		$this->addColumnIfNotExists('networks', 'updated_at', $this->timestamp(), true);
		$this->addColumnIfNotExists('networks', 'updated_by', $this->string(32), true);
		
		$this->addColumnIfNotExists('lic_keys', 'updated_at', $this->timestamp(), true);
		$this->addColumnIfNotExists('lic_keys', 'updated_by', $this->string(32), true);

		$this->addColumnIfNotExists('lic_items', 'updated_at', $this->timestamp(), true);
		$this->addColumnIfNotExists('lic_items', 'updated_by', $this->string(32), true);
		$this->addColumnIfNotExists('lic_items', 'services_id', $this->integer(), true);
		
		$this->addColumnIfNotExists('lic_groups', 'services_id', $this->integer(), true);
		
		$this->addColumnIfNotExists('services_history', 'lic_items_ids', $this->text());
		$this->addColumnIfNotExists('services_history', 'lic_groups_ids', $this->text());
		
		$this->createTable('segments_history', [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
			'updated_comment' => $this->string(),
			'changed_attributes' => $this->text(),
			
			// Поля из rules
			'name' => $this->string(32),
			'code' => $this->string(255),
			'description' => $this->string(255),
			'history' => $this->text(),
			'links' => $this->text(),
			
			// Поля из linksSchema
			//'services_ids' => $this->text(),	//не будем мы наверно это сохранять в истории сегментов
			//'networks_ids' => $this->text(),  //тут это как-то не в тему
		]);
		$this->createIndex('segments_history-master_id', 'segments_history', 'master_id');
		$this->createIndex('segments_history-updated_at', 'segments_history', 'updated_at');
		$this->createIndex('segments_history-updated_by', 'segments_history', 'updated_by');
		
		
		$this->createTable('networks_history', [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
			'updated_comment' => $this->string(),
			'changed_attributes' => $this->text(),
			
			// Поля из rules
			'name' => $this->string(255),
			'text_addr' => $this->string(),
			'text_router' => $this->string(),
			'text_dhcp' => $this->string(),
			'comment' => $this->text(),
			'notepad' => $this->text(),
			'ranges' => $this->text(),
			'links' => $this->text(),
			'archived' => $this->boolean(),
			
			// Поля из linksSchema
			'vlan_id' => $this->integer(),
			'segments_id' => $this->integer(),
			'org_inets_ids' => $this->text(),
		]);
		
		$this->createIndex('networks_history-master_id', 'networks_history', 'master_id');
		$this->createIndex('networks_history-updated_at', 'networks_history', 'updated_at');
		$this->createIndex('networks_history-updated_by', 'networks_history', 'updated_by');
		
		
		$this->createTable('lic_items_history', [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
			'updated_comment' => $this->string(),
			'changed_attributes' => $this->text(),
			
			// Поля из rules
			'descr' => $this->string(255),
			'count' => $this->integer(),
			'comment' => $this->text(),
			'active_from' => $this->date(),
			'active_to' => $this->date(),
			'created_at' => $this->timestamp(),
			
			// Поля из linksSchema
			'lic_group_id' => $this->integer(),
			'services_id' => $this->integer(),
			'contracts_ids' => $this->text(),
			'arms_ids' => $this->text(),
			'comps_ids' => $this->text(),
			'users_ids' => $this->text(),
			'licKeys_ids' => $this->text(),
		]);
		
		$this->createIndex('lic_items_history-master_id', 'lic_items_history', 'master_id');
		$this->createIndex('lic_items_history-updated_at', 'lic_items_history', 'updated_at');
		$this->createIndex('lic_items_history-updated_by', 'lic_items_history', 'updated_by');
		
		
		$this->createTable('lic_groups_history', [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
			'updated_comment' => $this->string(),
			'changed_attributes' => $this->text(),
			
			// Поля из rules
			'descr' => $this->string(255),
			'comment' => $this->text(),
			'created_at' => $this->timestamp(),
			
			// Поля из linksSchema
			'lic_types_id' => $this->integer(),
			'services_id' => $this->integer(),
			'arms_ids' => $this->text(),
			'comps_ids' => $this->text(),
			'users_ids' => $this->text(),
		]);
		
		$this->createIndex('lic_groups_history-master_id', 'lic_groups_history', 'master_id');
		$this->createIndex('lic_groups_history-updated_at', 'lic_groups_history', 'updated_at');
		$this->createIndex('lic_groups_history-updated_by', 'lic_groups_history', 'updated_by');
		
		
		$this->createTable('lic_keys_history', [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer(),
			'updated_at' => $this->timestamp(),
			'updated_by' => $this->string(32),
			'updated_comment' => $this->string(),
			'changed_attributes' => $this->text(),
			
			// Поля из rules
			'key_text' => $this->text(),
			'comment' => $this->text(),
			
			// Поля из linksSchema
			'lic_items_id' => $this->integer(),
			'arms_ids' => $this->text(),
			'comps_ids' => $this->text(),
			'users_ids' => $this->text(),
		]);
		
		$this->createIndex('lic_keys_history-master_id', 'lic_keys_history', 'master_id');
		$this->createIndex('lic_keys_history-updated_at', 'lic_keys_history', 'updated_at');
		$this->createIndex('lic_keys_history-updated_by', 'lic_keys_history', 'updated_by');
	}

    /**
     * {@inheritdoc}
     */
	public function down()
	{
		$this->dropTableIfExists('segments_history');
		$this->dropTableIfExists('networks_history');
		$this->dropTableIfExists('lic_items_history');
		$this->dropTableIfExists('lic_groups_history');
		$this->dropTableIfExists('lic_keys_history');
		
		$this->dropColumnIfExists('services_history', 'lic_items_ids');
		$this->dropColumnIfExists('services_history', 'lic_groups_ids');
		
		$this->dropColumnIfExists('segments', 'updated_at');
		$this->dropColumnIfExists('segments', 'updated_by');
		
		$this->dropColumnIfExists('networks', 'updated_at');
		$this->dropColumnIfExists('networks', 'updated_by');
		
		$this->dropColumnIfExists('lic_keys', 'updated_at');
		$this->dropColumnIfExists('lic_keys', 'updated_by');
		
		$this->dropColumnIfExists('lic_items', 'updated_at');
		$this->dropColumnIfExists('lic_items', 'updated_by');
		$this->dropColumnIfExists('lic_items', 'services_id');
		
		$this->dropColumnIfExists('lic_groups', 'services_id');
	}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250828100718AdditionalHistory cannot be reverted.\n";

        return false;
    }
    */
}
