<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M231226142737CreateTableJobs
 */
class M231226142737CreateTableJobs extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$this->dropTableIfExists('maintenance_reqs');
		$this->createTable('maintenance_reqs',[
			'id'=>$this->primaryKey(),
			'name'=>$this->string()->notNull(),
			'description'=>$this->string(1024)->notNull(),
			'is_backup'=>$this->boolean()->defaultValue(false),
			'spread_comps'=>$this->boolean()->defaultValue(true),
			'spread_techs'=>$this->boolean()->defaultValue(true),
			'links'=>$this->text(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
		],'engine=InnoDB');
		$this->createIndex('maintenance_reqs-name','maintenance_reqs','name');
		$this->createIndex('maintenance_reqs-description','maintenance_reqs','description');
	
		$this->dropTableIfExists('maintenance_reqs_history');
		$this->createTable('maintenance_reqs_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'name'=>$this->string(),
			'description'=>$this->string(1024),
			'spread_comps'=>$this->boolean(),
			'spread_techs'=>$this->boolean(),
			'links'=>$this->text(),
			'services_ids'=>$this->text(),
			'comps_ids'=>$this->text(),
			'techs_ids'=>$this->text(),
			'includes_ids'=>$this->text(),
			'included_ids'=>$this->text(),
			'jobs_ids'=>$this->text(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		],'engine=InnoDB');
		$this->createIndex('maintenance_reqs_history-master_id','maintenance_reqs_history','master_id');
		$this->createIndex('maintenance_reqs_history-updated_at','maintenance_reqs_history','master_id');
		$this->createIndex('maintenance_reqs_history-updated_by','maintenance_reqs_history','updated_by');
	
	
		$this->createMany2ManyTable('maintenance_reqs_in_services',[
			'reqs_id'=>'maintenance_reqs',
			'services_id'=>'services',
		]);
		$this->createMany2ManyTable('maintenance_reqs_in_comps',[
			'reqs_id'=>'maintenance_reqs',
			'comps_id'=>'comps',
		]);
		$this->createMany2ManyTable('maintenance_reqs_in_techs',[
			'reqs_id'=>'maintenance_reqs',
			'techs_id'=>'techs',
		]);
		$this->createMany2ManyTable('maintenance_reqs_in_reqs',[
			'reqs_id'=>'maintenance_reqs',
			'includes_id'=>'maintenance_reqs',
		]);
	
		$this->dropTableIfExists('maintenance_jobs');
		$this->createTable('maintenance_jobs',[
			'id'=>$this->primaryKey(),
			'name'=>$this->string()->notNull(),
			'description'=>$this->string(1024)->notNull(),
			'schedules_id'=>$this->integer()->null(),
			'services_id'=>$this->integer()->null(),
			'links'=>$this->text(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
		],'engine=InnoDB');
		$this->createIndex('maintenance_jobs-name','maintenance_jobs','name');
		$this->createIndex('maintenance_jobs-description','maintenance_jobs','description');
		$this->createIndex('maintenance_jobs-schedules_id','maintenance_jobs','schedules_id');
		$this->createIndex('maintenance_jobs-services_id','maintenance_jobs','services_id');
	
		$this->dropTableIfExists('maintenance_jobs_history');
		$this->createTable('maintenance_jobs_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'name'=>$this->string(),
			'description'=>$this->string(1024),
			'schedules_id'=>$this->integer()->null(),
			'services_id'=>$this->integer()->null(),
			'links'=>$this->text(),
			'services_ids'=>$this->text(),
			'comps_ids'=>$this->text(),
			'techs_ids'=>$this->text(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		],'engine=InnoDB');
		$this->createIndex('maintenance_jobs_history-master_id','maintenance_jobs_history','master_id');
		$this->createIndex('maintenance_jobs_history-updated_at','maintenance_jobs_history','master_id');
		$this->createIndex('maintenance_jobs_history-updated_by','maintenance_jobs_history','updated_by');
	
		$this->createMany2ManyTable('maintenance_reqs_in_jobs',[
			'reqs_id'=>'maintenance_reqs',
			'jobs_id'=>'maintenance_jobs',
		]);
		$this->createMany2ManyTable('maintenance_jobs_in_services',[
			'services_id'=>'services',
			'jobs_id'=>'maintenance_jobs',
		]);
		$this->createMany2ManyTable('maintenance_jobs_in_comps',[
			'comps_id'=>'comps',
			'jobs_id'=>'maintenance_jobs',
		]);
		$this->createMany2ManyTable('maintenance_jobs_in_techs',[
			'techs_id'=>'techs',
			'jobs_id'=>'maintenance_jobs',
		]);
		
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropTableIfExists('maintenance_jobs_in_techs');
		$this->dropTableIfExists('maintenance_jobs_in_comps');
		$this->dropTableIfExists('maintenance_jobs_in_services');
		$this->dropTableIfExists('maintenance_jobs_history');
	
		$this->dropTableIfExists('maintenance_reqs_in_jobs');
		$this->dropTableIfExists('maintenance_reqs_in_reqs');
		$this->dropTableIfExists('maintenance_reqs_in_comps');
		$this->dropTableIfExists('maintenance_reqs_in_techs');
		$this->dropTableIfExists('maintenance_reqs_in_services');
		$this->dropTableIfExists('maintenance_reqs_history');

		$this->dropTableIfExists('maintenance_jobs');
		$this->dropTableIfExists('maintenance_reqs');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M231226142737CreateTableJobs cannot be reverted.\n";

        return false;
    }
    */
}
