<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240401113410ServiceConnections
 */
class M240401113410ServiceConnections extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	if (!$this->tableExists('service_connections')) {
			$this->createTable('service_connections',[
				'id'=>$this->primaryKey(),
				'initiator_id'=>$this->integer()->null(),
				'target_id'=>$this->integer()->null(),
				'initiator_details'=>$this->string(),
				'target_details'=>$this->string(),
				'comment'=>$this->string(),
	
				'updated_at'=>$this->timestamp(),
				'updated_by'=>$this->string(32),
			]);
			$this->createIndex('service_connections_initiator','service_connections','initiator_id');
			$this->createIndex('service_connections_target','service_connections','target_id');
		}
		$this->createMany2ManyTable('comps_in_targets',['connection_id','comps_id']);
		$this->createMany2ManyTable('techs_in_targets',['connection_id','techs_id']);
		$this->createMany2ManyTable('comps_in_initiators',['connection_id','comps_id']);
		$this->createMany2ManyTable('techs_in_initiators',['connection_id','techs_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropTableIfExists('comps_in_targets');
		$this->dropTableIfExists('techs_in_targets');
		$this->dropTableIfExists('comps_in_initiators');
		$this->dropTableIfExists('techs_in_initiators');
        $this->dropTableIfExists('service_connections');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240401113410ServiceConnections cannot be reverted.\n";

        return false;
    }
    */
}
