<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Class M240128150114HistoryJournalsContracts
 */
class M240128150114HistoryJournalsContracts extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumnIfNotExist('contracts','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('contracts','updated_by',$this->string(32));
  
		$this->createTable('contracts_history',[
			'id'=>$this->primaryKey(),
			'master_id'=>$this->integer(),
			'updated_at'=>$this->timestamp(),
			'updated_by'=>$this->string(32),
			'updated_comment'=>$this->string(),
			'changed_attributes'=>$this->text(),
			
			'partners_ids' => $this->text(),
			'lics_ids' => $this->text(),
			'techs_ids' =>  $this->text(),
			'services_ids' => $this->text(),
			'materials_ids' => $this->text(),
			'users_ids' => $this->text(),
		
			'parent_id' => $this->integer(),
			'is_sucessor' => $this->boolean(),
			
			'date' => $this->date(),
			'end_date' => $this->date(),
			
			'name'=> $this->string(128),
			
			'state_id'=> $this->integer(),
			
			'comment'=> $this->text(),
			
			'total'=>$this->decimal(15,2),
			'charge'=>$this->decimal(15,2),
			'currency_id'=> $this->integer(),

			'pay_id'=> $this->string(),
			
			'techs_delivery'=> $this->integer(),
			'materials_delivery'=> $this->integer(),
			'lics_delivery'=> $this->integer(),
		]);
		$this->createIndex('contracts_history-master_id','contracts_history','master_id');
		$this->createIndex('contracts_history-updated_at','contracts_history','updated_at');
		$this->createIndex('contracts_history-updated_by','contracts_history','updated_by');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('contracts_history');
		$this->dropColumnIfExist('contracts','updated_at');
		$this->dropColumnIfExist('contracts','updated_by');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M240128150114HistoryJournalsContracts cannot be reverted.\n";

        return false;
    }
    */
}
