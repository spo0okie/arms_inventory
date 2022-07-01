<?php

use yii\db\Migration;

/**
 * Class m220630_173032_alter_tables_prov_tel
 */
class m220630_173032_alter_tables_prov_tel extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table = $this->db->getTableSchema('org_inet');
        
        if (isset($table->foreignKeys['org_inet_ibfk_2']))
            $this->dropForeignKey('org_inet_ibfk_2','org_inet');

		if (isset($table->columns['prov_tel_id']))
			$this->dropColumn('org_inet','prov_tel_id');

        if (isset($table->columns['contracts_id']))
			$this->dropColumn('org_inet','contracts_id');

        $this->createIndex('services_id','org_inet','services_id');
	
		$this->createIndex('networks_id','org_inet','networks_id');

        $table = $this->db->getTableSchema('org_phones');

        if (isset($table->foreignKeys['org_phones_ibfk_1']))
			$this->dropForeignKey('org_phones_ibfk_1','org_phones');
    
        if (isset($table->foreignKeys['org_phones_ibfk_3']))
			$this->dropForeignKey('org_phones_ibfk_3','org_phones');
    
        if (isset($table->columns['prov_tel_id']))
			$this->dropColumn('org_phones','prov_tel_id');
    
        if (isset($table->columns['contracts_id']))
			$this->dropColumn('org_phones','contracts_id');
	
		$this->createIndex('services_id','org_phones','services_id');

        if (!is_null($this->db->getTableSchema('prov_tel'))) {
            $this->dropTable('prov_tel');
        }
        
            
    }
    

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220630_173032_alter_tables_prov_tel cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220630_173032_alter_tables_prov_tel cannot be reverted.\n";

        return false;
    }
    */
}
