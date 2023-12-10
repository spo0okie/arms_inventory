<?php

namespace app\migrations;

use yii\db\Migration;
use yii\db\Query;

/**
 * Class M231209133554AlterTableNetworks
 */
class M231209133554AlterTableNetworks extends Migration
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
		$this->addColumnIfNotExist('networks','archived',$this->boolean(),true);
		$this->addColumnIfNotExist('networks','links',$this->text());
		$this->addColumnIfNotExist('networks','ranges',$this->text());
		$this->addColumnIfNotExist('networks','text_dhcp',$this->text());
	
		$query=new Query();
		$networks=$query->select('*')->from('networks')->all();
		foreach ($networks as $network) if ($network['dhcp']) {
			$id=$network['id'];
			$text_dhcp=long2ip($network['dhcp']);
			echo "setting $text_dhcp\n";
			$this->execute(
				'update networks set text_dhcp=:text_dhcp where id=:id',[
				'id'=>$id,
				'text_dhcp'=>$text_dhcp,
			]);
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('networks','archived');
		$this->dropColumnIfExist('networks','links');
		$this->dropColumnIfExist('networks','ranges');
		$this->dropColumnIfExist('networks','text_dhcp');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M231209133554AlterTableNetworks cannot be reverted.\n";

        return false;
    }
    */
}
