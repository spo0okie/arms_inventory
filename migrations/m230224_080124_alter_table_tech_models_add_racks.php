<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230305_082924_alter_table_tech_models_add_racks
 */
class m230224_080124_alter_table_tech_models_add_racks extends Migration
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
		$this->addColumnIfNotExist('tech_models','front_rack_layout',$this->text()->null());
		$this->addColumnIfNotExist('tech_models','contain_front_rack',$this->boolean()->defaultValue(false));
		$this->addColumnIfNotExist('tech_models','front_rack_two_sided',$this->boolean()->defaultValue(false));
		
		$this->addColumnIfNotExist('tech_models','back_rack_layout',$this->text()->null());
		$this->addColumnIfNotExist('tech_models','contain_back_rack',$this->boolean()->defaultValue(false));
		$this->addColumnIfNotExist('tech_models','back_rack_two_sided',$this->boolean()->defaultValue(false));
	
		$this->addColumnIfNotExist('techs','installed_back',$this->boolean()->defaultValue(false));
		$this->addColumnIfNotExist('techs','full_length',$this->boolean()->defaultValue(false));
		/** @noinspection SqlWithoutWhere */
		$this->execute('update techs set installed_pos=null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

		$this->dropColumnIfExist('tech_models','front_rack_layout');
		$this->dropColumnIfExist('tech_models','contain_front_rack');
		$this->dropColumnIfExist('tech_models','front_rack_two_sided');
	
		$this->dropColumnIfExist('tech_models','back_rack_layout');
		$this->dropColumnIfExist('tech_models','contain_back_rack');
		$this->dropColumnIfExist('tech_models','back_rack_two_sided');
	
		$this->dropColumnIfExist('techs','installed_back');
		$this->dropColumnIfExist('techs','full_length');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230305_082924_alter_table_tech_models_add_racks cannot be reverted.\n";

        return false;
    }
    */
}
