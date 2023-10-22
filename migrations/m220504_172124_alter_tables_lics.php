<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m220504_172124_alter_tables_lics
 */
class m220504_172124_alter_tables_lics extends Migration
{
	
	public $objs=['comps','arms','users'];
	public $lics=['groups','items','keys'];
	public function tableFields() {return[
		'comment'=>$this->text(),
		'updated_by'=> $this->integer(),
		'updated_at'=>$this->string(),
		'created_by'=>$this->integer(),
		'created_at'=>$this->string(),
	];}
	
	
	public function upgradeTable($tableName) {
		$table = $this->db->getTableSchema($tableName);
		foreach ($this->tableFields() as $field=>$type)
			if (!isset($table->columns[$field]))
				$this->addColumn($tableName, $field, $type);
	}
	
	public function downgradeTable($tableName) {
		$table = $this->db->getTableSchema($tableName);
		foreach ($this->tableFields() as $field=>$type)
			if (isset($table->columns[$field]))
				$this->dropColumn($tableName, $field);
	}

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		foreach ($this->lics as $lic)
			foreach ($this->objs as $obj)
				$this->upgradeTable("lic_${lic}_in_${obj}");
	
		$table = $this->db->getTableSchema('lic_groups_in_arms');
		if (isset($table->columns['lics_id']))
			$this->renameColumn('lic_groups_in_arms','lics_id','lic_groups_id');
		
		
		$table = $this->db->getTableSchema('lic_items_in_arms');
		
		if (isset($table->foreignKeys['lic_items_in_arms_ibfk_2']))
			$this->dropForeignKey('lic_items_in_arms_ibfk_2','lic_items_in_arms');
		
		if (isset($table->columns['lics_id']))
			$this->renameColumn('lic_items_in_arms','lics_id','lic_items_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		foreach ($this->lics as $lic)
			foreach ($this->objs as $obj)
				$this->downgradeTable("lic_${lic}_in_${obj}");

		$table = $this->db->getTableSchema('lic_groups_in_arms');
		if (isset($table->columns['lic_groups_id']))
			$this->renameColumn('lic_groups_in_arms','lic_groups_id','lics_id');
	
		$table = $this->db->getTableSchema('lic_items_in_arms');
		if (isset($table->columns['lic_items_id']))
			$this->renameColumn('lic_items_in_arms','lic_items_id','lics_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220504_172124_alter_tables_lics cannot be reverted.\n";

        return false;
    }
    */
}
