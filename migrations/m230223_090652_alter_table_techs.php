<?php

use yii\db\Migration;

/**
 * Class m230223_090652_alter_table_techs
 */
class m230223_090652_alter_table_techs extends Migration
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
		$this->execute("ALTER TABLE techs ROW_FORMAT=DYNAMIC");
    	$this->addColumnIfNotExist('techs','comp_id',$this->integer()->null(),true);
		$this->addColumnIfNotExist('techs','installed_id',$this->integer()->null(),true);
		$this->addColumnIfNotExist('techs','installed_pos',$this->string(16));
		$this->addColumnIfNotExist('techs','head_id',$this->integer()->null(),true);
		$this->addColumnIfNotExist('techs','responsible_id',$this->integer()->null(),true);
		$this->addColumnIfNotExist('techs','hw',$this->text());
		$this->addColumnIfNotExist('techs','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('techs','installed_pos_end',$this->string(128));
		$this->alterColumn('techs','installed_pos',$this->string(128));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('techs','comp_id');
		$this->dropColumnIfExist('techs','installed_id');
		$this->dropColumnIfExist('techs','installed_pos');
		$this->dropColumnIfExist('techs','head_id');
		$this->dropColumnIfExist('techs','responsible_id');
		$this->dropColumnIfExist('techs','hw');
		$this->dropColumnIfExist('techs','updated_at');
		$this->dropColumnIfExist('techs','installed_pos_end');
		//$this->alterColumn('techs','installed_pos',$this->string(128));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230223_090652_alter_table_techs cannot be reverted.\n";

        return false;
    }
    */
}
