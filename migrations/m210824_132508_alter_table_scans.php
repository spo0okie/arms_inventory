<?php

use yii\db\Migration;

/**
 * Class m210824_132508_alter_table_scans
 */
class m210824_132508_alter_table_scans extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('scans');
		$this->alterColumn('scans','contracts_id',$this->integer()->null());

		if (!isset($table->columns['places_id'])) {
			$this->addColumn('scans','places_id',$this->integer()->null());
			$this->createIndex('idx-scans_places_id','scans','places_id');
		}
	
		if (!isset($table->columns['tech_models_id'])) {
			$this->addColumn('scans', 'tech_models_id', $this->integer()->null());
			$this->createIndex('idx-scans_tech_models_id','scans','tech_models_id');
		}
		
		if (!isset($table->columns['material_models_id'])) {
			$this->addColumn('scans', 'material_models_id', $this->integer()->null());
			$this->createIndex('idx-scans_material_models_id','scans','material_models_id');
		}
	
		if (!isset($table->columns['lic_types_id'])) {
			$this->addColumn('scans', 'lic_types_id', $this->integer()->null());
			$this->createIndex('idx-scans_lic_types_id','scans','lic_types_id');
		}
	
		if (!isset($table->columns['lic_items_id'])){
			$this->addColumn('scans','lic_items_id',$this->integer()->null());
			$this->createIndex('idx-scans_lic_items_id','scans','lic_items_id');
		}
	
		$table=$this->db->getTableSchema('tech_models');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('tech_models', 'scans_id', $this->integer()->null());
		}
	
		$table=$this->db->getTableSchema('places');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('places', 'scans_id', $this->integer()->null());
		}
	
		$table=$this->db->getTableSchema('materials_types');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('materials_types', 'scans_id', $this->integer()->null());
		}
	
		$table=$this->db->getTableSchema('lic_types');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('lic_types', 'scans_id', $this->integer()->null());
		}
	
		$table=$this->db->getTableSchema('lic_items');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('lic_items', 'scans_id', $this->integer()->null());
		}
	
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('scans');
		if (isset($table->columns['places_id']))
			$this->dropColumn('scans','places_id');
	
		if (isset($table->columns['tech_models_id']))
			$this->dropColumn('scans','tech_models_id');
	
		if (isset($table->columns['material_models_id']))
			$this->dropColumn('scans','material_models_id');
	
		if (isset($table->columns['lic_types_id']))
			$this->dropColumn('scans','lic_types_id');
	
		if (isset($table->columns['lic_items_id']))
			$this->dropColumn('scans','lic_items_id');
	
	
		$table=$this->db->getTableSchema('tech_models');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('tech_models', 'scans_id');
	
		$table=$this->db->getTableSchema('places');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('places', 'scans_id');
	
		$table=$this->db->getTableSchema('materials_types');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('materials_types', 'scans_id');
	
		$table=$this->db->getTableSchema('lic_types');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('lic_types', 'scans_id');
	
		$table=$this->db->getTableSchema('lic_items');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('lic_items', 'scans_id');
		
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210824_132508_alter_table_scans cannot be reverted.\n";

        return false;
    }
    */
}
