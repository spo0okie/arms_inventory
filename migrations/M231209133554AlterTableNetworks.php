<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;
use yii\db\Query;

/**
 * Class M231209133554AlterTableNetworks
 */
class M231209133554AlterTableNetworks extends ArmsMigration
{
	
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExists('networks','archived',$this->boolean(),true);
		$this->addColumnIfNotExists('networks','links',$this->text());
		$this->addColumnIfNotExists('networks','ranges',$this->text());
		$this->addColumnIfNotExists('networks','text_dhcp',$this->text());
	
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
		$this->dropColumnIfExists('networks','archived');
		$this->dropColumnIfExists('networks','links');
		$this->dropColumnIfExists('networks','ranges');
		$this->dropColumnIfExists('networks','text_dhcp');
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
