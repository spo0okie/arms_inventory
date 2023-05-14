<?php

use yii\db\Migration;

/**
 * Class m230310_125905_create_table_attaches
 */
class m230513_125905_create_table_attaches extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		if (is_null($table = $this->db->getTableSchema('attaches'))) {
			$this->createTable('attaches', [
				'id' => $this->primaryKey(),
				'techs_id' => $this->integer()->null(),
				'services_id' => $this->integer()->null(),
				'lic_types_id' => $this->integer()->null(),
				'lic_groups_id' => $this->integer()->null(),
				'lic_items_id' => $this->integer()->null(),
				'lic_keys_id' => $this->integer()->null(),
				'contracts_id' => $this->integer()->null(),
				'places_id' => $this->integer()->null(),
				'schedules_id' => $this->integer()->null(),
				'filename' => $this->string(),
			]);
			
			$this->createIndex('idx-attaches-techs','attaches','techs_id');
			$this->createIndex('idx-attaches-services','attaches','services_id');
			$this->createIndex('idx-attaches-lic_types','attaches','lic_types_id');
			$this->createIndex('idx-attaches-lic_groups','attaches','lic_groups_id');
			$this->createIndex('idx-attaches-lic_items','attaches','lic_items_id');
			$this->createIndex('idx-attaches-lic_keys','attaches','lic_keys_id');
			$this->createIndex('idx-attaches-contracts','attaches','contracts_id');
			$this->createIndex('idx-attaches-places','attaches','places_id');
			$this->createIndex('idx-attaches-schedules','attaches','schedules_id');
		}
		
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($table = $this->db->getTableSchema('attaches'))) {
			$this->dropTable('attaches');
		}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230310_125905_create_table_attaches cannot be reverted.\n";

        return false;
    }
    */
}
