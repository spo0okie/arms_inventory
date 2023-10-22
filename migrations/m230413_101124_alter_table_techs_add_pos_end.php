<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m230413_101124_alter_table_techs_add_pos_end
 */
class m230413_101124_alter_table_techs_add_pos_end extends Migration
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
		$this->addColumnIfNotExist('techs','installed_pos_end',$this->string(128));
		$this->alterColumn('techs','installed_pos',$this->string(128));
			
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('techs','installed_pos_end');
		$this->alterColumn('techs','installed_pos',$this->string(16));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230413_101124_alter_table_techs_add_pos_end cannot be reverted.\n";

        return false;
    }
    */
}
