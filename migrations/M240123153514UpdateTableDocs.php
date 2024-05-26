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
		$this->addColumnIfNotExists('contracts_states','paid',$this->boolean()->defaultValue(0),true);
		$this->addColumnIfNotExists('contracts_states','unpaid',$this->boolean()->defaultValue(0),true);
		$this->execute('update contracts_states set paid=1 where `code` like "state_payed%"');
		$this->execute('update contracts_states set unpaid=1 where `code` like "state_paywait_%"');
		$this->execute('update contracts_states set unpaid=1 where `code` = "state_payed_partial"');
	
		$this->addColumnIfNotExists('contracts','pay_id',$this->string(),true);
		$this->addColumnIfNotExists('contracts','techs_delivery',$this->integer(),true);
		$this->addColumnIfNotExists('contracts','materials_delivery',$this->integer(),true);
		$this->addColumnIfNotExists('contracts','lics_delivery',$this->integer(),true);
		
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExists('contracts_states','paid');
		$this->dropColumnIfExists('contracts_states','unpaid');

		$this->dropColumnIfExists('contracts','pay_id');
		$this->dropColumnIfExists('contracts','techs_delivery');
		$this->dropColumnIfExists('contracts','materials_delivery');
		$this->dropColumnIfExists('contracts','lics_delivery');
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
