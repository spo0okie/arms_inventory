<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m211002_062719_alter_table_services
 */
class m211002_062719_alter_table_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
	{
		$table = $this->db->getTableSchema('services');
		if (!isset($table->columns['is_service']))
			$this->addColumn('services', 'is_service', $this->boolean()->defaultValue(1));
		
		if (!is_null($table = $this->db->getTableSchema('services'))) {
			if (!isset($table->columns['currency_id'])){
				$this->addColumn('services','currency_id',$this->integer()->notNull()->defaultValue(1));
				$this->createIndex('idx-services-currency_id','services','currency_id');
			}
			
		}
		
		if (is_null($table = $this->db->getTableSchema('contracts_in_services'))) {
			$this->createTable('contracts_in_services', [
				'[[id]]' => $this->primaryKey(),        	//ключ
				'[[services_id]]' => $this->integer(),      //сервис
				'[[contracts_id]]' => $this->integer(),     //документ
			]);
		
			$this->createIndex('{{%idx-contracts_in_services_cid}}', '{{%contracts_in_services}}', '[[contracts_id]]');
			$this->createIndex('{{%idx-contracts_in_services_sid}}', '{{%contracts_in_services}}', '[[services_id]]');
		}
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('services');
		if (isset($table->columns['is_service']))
			$this->dropColumn('services','is_service');

		if (isset($table->columns['currency_id']))
			$this->dropColumn('services','currency_id');
	
		if (!is_null($table = $this->db->getTableSchema('contracts_in_services')))
				$this->dropTable('contracts_in_services');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211002_062719_alter_table_services cannot be reverted.\n";

        return false;
    }
    */
}
