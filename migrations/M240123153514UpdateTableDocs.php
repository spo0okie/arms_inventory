<?php /** @noinspection SqlResolve */

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240123153514UpdateTableDocs
 */
class M240123153514UpdateTableDocs extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExist('contracts_states','paid',$this->boolean()->defaultValue(0),true);
		$this->addColumnIfNotExist('contracts_states','unpaid',$this->boolean()->defaultValue(0),true);
		$this->execute('update contracts_states set paid=1 where `code` like "state_payed%"');
		$this->execute('update contracts_states set unpaid=1 where `code` like "state_paywait_%"');
		$this->execute('update contracts_states set unpaid=1 where `code` = "state_payed_partial"');
	
		$this->addColumnIfNotExist('contracts','pay_id',$this->string(),true);
		$this->addColumnIfNotExist('contracts','techs_delivery',$this->integer(),true);
		$this->addColumnIfNotExist('contracts','materials_delivery',$this->integer(),true);
		$this->addColumnIfNotExist('contracts','lics_delivery',$this->integer(),true);
		
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('contracts_states','paid');
		$this->dropColumnIfExist('contracts_states','unpaid');

		$this->dropColumnIfExist('contracts','pay_id');
		$this->dropColumnIfExist('contracts','techs_delivery');
		$this->dropColumnIfExist('contracts','materials_delivery');
		$this->dropColumnIfExist('contracts','lics_delivery');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240123153514UpdateTableDocs cannot be reverted.\n";

        return false;
    }
    */
}
