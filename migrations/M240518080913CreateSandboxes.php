<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240518080913CreateSandboxes
 */
class M240518080913CreateSandboxes extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (!$this->tableExists('sandboxes')) {
			$this->createTable('sandboxes', [
				'id' => $this->primaryKey(),
				'name' => $this->string(64),
				'suffix' => $this->string(12),
				'network_accessible' => $this->boolean(),
				'notepad' => $this->text(),
				'links' => $this->text(),
				'archived' => $this->boolean(),
				
				'updated_at' => $this->timestamp(),
				'updated_by' => $this->string(32),
			]);
			
			if (!$this->tableExists('sandboxes_history')) {
				$this->createTable('sandboxes_history', [
					'id' => $this->primaryKey(),
					'master_id' => $this->integer(),
					'updated_at' => $this->timestamp(),
					'updated_by' => $this->string(32),
					'updated_comment' => $this->string(),
					'changed_attributes' => $this->text(),
					'archived' => $this->boolean(),
					
					'name' => $this->string(64),
					'suffix' => $this->string(12),
					'network_accessible' => $this->boolean(),
					'notepad' => $this->text(),
					'links' => $this->text(),
					'comps_ids' => $this->text(),
				]);
				$this->createIndex('sandboxes_history-master_id', 'sandboxes_history', 'master_id');
				$this->createIndex('sandboxes_history-updated_at', 'sandboxes_history', 'updated_at');
				$this->createIndex('sandboxes_history-updated_by', 'sandboxes_history', 'updated_by');
			}
		}
		
		$this->addColumnIfNotExists('comps', 'sandbox_id', $this->integer()->null(), true);
		$this->addColumnIfNotExists('comps_history', 'sandbox_id', $this->integer()->null(), true);
		
		$this->dropFkIfExists('domains', 'comps');
		$this->dropIndexIfExists('domainname', 'comps');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('comps', 'sandbox_id');
		$this->dropColumnIfExists('comps', 'sandbox_id');
		$this->dropTableIfExists('sandboxes_history');
		$this->dropTableIfExists('sandboxes');
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "M240518080913CreateSandboxes cannot be reverted.\n";

		return false;
	}
	*/
}
