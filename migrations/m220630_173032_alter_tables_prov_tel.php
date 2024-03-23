<?php
namespace app\migrations;
use app\migrations\arms\ArmsMigration;

/**
 * Class m220630_173032_alter_tables_prov_tel
 */
class m220630_173032_alter_tables_prov_tel extends ArmsMigration
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
		$table = $this->db->getTableSchema('org_inet');
	
		$this->addColumnIfNotExist('org_inet','prov_tel_id',$this->integer());
		$this->addColumnIfNotExist('org_inet','contracts_id',$this->integer());
		$this->addColumnIfNotExist('org_phones','prov_tel_id',$this->integer());
		$this->addColumnIfNotExist('org_phones','contracts_id',$this->integer());
	
		if (is_null($this->db->getTableSchema('prov_tel'))) {
			$this->execute(<<<SQL
				CREATE TABLE `prov_tel` (
				  `id` int(11) NOT NULL COMMENT 'id',
				  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
				  `cabinet_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Личный кабинет',
				  `support_tel` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Телефон поддержки',
				  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Комментарий'
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Поставки услуг телефонии';
SQL
);
		}
	
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
