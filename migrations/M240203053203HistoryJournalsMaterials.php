<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240203053203HistoryJournalsMaterials
 */
class M240203053203HistoryJournalsMaterials extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('materials','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('materials','updated_by',$this->string(32),true);
		$this->addColumnIfNotExists('materials_types','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('materials_types','updated_by',$this->string(32),true);
		$this->addColumnIfNotExists('materials_usages','updated_at',$this->timestamp(),true);
		$this->addColumnIfNotExists('materials_usages','updated_by',$this->string(32),true);
		$this->addColumnIfNotExists('techs_history','materials_usages_ids',$this->text());
		$this->dropColumnIfExists('materials_usages','arms_id');
		
		$this->createTable('materials_types_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'code'=>$this->string(12),
			'name'=>$this->string(128),
			'units'=>$this->string(16),
			'comment'=>$this->text(),
			'scans_id'=>$this->integer(),
		]);
		$this->createIndex('materials_types_history-master_id','materials_types_history','master_id');
		$this->createIndex('materials_types_history-updated_at','materials_types_history','updated_at');
		$this->createIndex('materials_types_history-updated_by','materials_types_history','updated_by');
	
		$this->createTable('materials_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'parent_id'=>$this->integer(),
			'date'=>$this->date(),
			'count'=>$this->integer(),
			'type_id'=>$this->integer(),
			'model'=>$this->string(128),
			'places_id'=>$this->integer(),
			'it_staff_id'=>$this->integer(),
			'currency_id'=>$this->integer(),
			'cost'=>$this->float(),
			'charge'=>$this->float(),
			'comment'=>$this->text(),
			'history'=>$this->text(),
			'contracts_ids'=>$this->text(),
			'usages_ids'=>$this->text(),
		]);
		$this->createIndex('materials_history-master_id','materials_history','master_id');
		$this->createIndex('materials_history-updated_at','materials_history','updated_at');
		$this->createIndex('materials_history-updated_by','materials_history','updated_by');

		$this->createTable('materials_usages_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
		
			'materials_id'=>$this->integer(),
			'count'=>$this->integer(),
			'date'=>$this->date(),
			'techs_id'=>$this->integer(),
			'comment'=>$this->text(),
			'history'=>$this->text(),
		]);
		$this->createIndex('materials_usages_history-master_id','materials_usages_history','master_id');
		$this->createIndex('materials_usages_history-updated_at','materials_usages_history','updated_at');
		$this->createIndex('materials_usages_history-updated_by','materials_usages_history','updated_by');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropTable('materials_history');
		$this->dropTable('materials_types_history');
		$this->dropTable('materials_usages_history');
		$this->dropColumnIfExists('materials','updated_at');
		$this->dropColumnIfExists('materials','updated_by');
		$this->dropColumnIfExists('materials_types','updated_at');
		$this->dropColumnIfExists('materials_types','updated_by');
		$this->dropColumnIfExists('materials_usages','updated_at');
		$this->dropColumnIfExists('materials_usages','updated_by');
		$this->dropColumnIfExists('techs_history','materials_usages_ids');
		$this->addColumnIfNotExists('materials_usages','arms_id',$this->integer(),true);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240203053203HistoryJournalsMaterials cannot be reverted.\n";

        return false;
    }
    */
}
