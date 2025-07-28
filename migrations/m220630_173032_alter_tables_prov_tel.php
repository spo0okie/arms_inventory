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
	public function up()
	{
		$this->dropFkIfExists('org_inet_ibfk_2', 'org_inet');
		$this->dropColumnIfExists('org_inet', 'prov_tel_id');
		$this->dropColumnIfExists('org_inet', 'contracts_id');
		
		$this->createIndex('services_id', 'org_inet', 'services_id');
		$this->createIndex('networks_id', 'org_inet', 'networks_id');
		
		$this->dropFkIfExists('org_phones_ibfk_1', 'org_phones');
		$this->dropFkIfExists('org_phones_ibfk_3', 'org_phones');
		
		$this->dropColumnIfExists('org_phones', 'prov_tel_id');
		$this->dropColumnIfExists('org_phones', 'contracts_id');
		
		$this->createIndex('services_id', 'org_phones', 'services_id');
		
		if (!is_null($this->db->getTableSchema('prov_tel'))) {
			$this->dropTable('prov_tel');
		}
	}
	
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->addColumnIfNotExists('org_inet', 'prov_tel_id', $this->integer());
		$this->addColumnIfNotExists('org_inet', 'contracts_id', $this->integer());
		$this->addColumnIfNotExists('org_phones', 'prov_tel_id', $this->integer());
		$this->addColumnIfNotExists('org_phones', 'contracts_id', $this->integer());
		
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
