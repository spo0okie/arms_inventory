<?php

use yii\db\Migration;

/**
 * Class m230828_123950_sync_prepare_2
 */
class m230828_123950_sync_prepare_2 extends Migration
{
	function addColumnIfNotExist($table,$column,$type,$index=false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table,$column,$type);
			if ($index) $this->createIndex("idx-$table-$column",$table,$column);
			
		}
	}
	
	function dropColumnIfExist($table,$column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table,$column);
		}
	}
	/**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('manufacturers','updated_at',$this->timestamp());
		$this->alterColumn('soft','created_at',$this->timestamp());
	
		$this->addColumnIfNotExist('tech_types','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('tech_types','updated_by',$this->string(32));

		$this->addColumnIfNotExist('tech_models','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('tech_models','updated_by',$this->string(32));
		$this->dropForeignKey('tech_models_ibfk_1','tech_models');
		$this->dropForeignKey('tech_models_ibfk_2','tech_models');
	
	
		$this->renameColumn('soft','created_at','updated_at');
		$this->addColumnIfNotExist('soft','updated_by',$this->string(32));
		$this->dropForeignKey('manufacturers_id','soft');
	
	
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('tech_types','updated_at');
		$this->dropColumnIfExist('tech_types','updated_by');
	
		$this->dropColumnIfExist('tech_models','updated_at');
		$this->dropColumnIfExist('tech_models','updated_by');
	
		$this->renameColumn('soft','updated_at','created_at');
		$this->dropColumnIfExist('soft','updated_by');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230828_123950_sync_prepare_2 cannot be reverted.\n";

        return false;
    }
    */
}
