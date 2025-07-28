<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавление услуг ИТ
 */
class m190101_100004_update4 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('services'))) {
			$this->createTable('services', [
				'id' => $this->primaryKey()->comment('id'),
				'name' => $this->string(64)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'description' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'links' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'is_end_user' => $this->boolean()->notNull(),
				'user_group_id' => $this->integer()->Null(),
				'sla_id' => $this->integer()->Null(),
				'notebook' => $this->text()->append(' COLLATE utf8mb4_unicode_ci')
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
			$this->createIndex(
				'{{%idx-services-is_end_user}}',
				'{{%services}}',
				'is_end_user'
			);
			
			$this->createIndex(
				'{{%idx-services-group_id}}',
				'{{%services}}',
				'user_group_id'
			);
			
			$this->createIndex(
				'{{%idx-services-sal_id}}',
				'{{%services}}',
				'sla_id'
			);
		}
		
		if (is_null($table = $this->db->getTableSchema('services_depends'))) {
			$this->createTable('services_depends', [
				'id' => $this->primaryKey()->comment('id'),
				'service_id' => $this->integer()->notNull(),
				'depends_id' => $this->integer()->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
			$this->createIndex(
				'{{%idx-services_depends-service_id}}',
				'{{%services_depends}}',
				'service_id'
			);
			
			$this->createIndex(
				'{{%idx-services_depends-depends_id}}',
				'{{%services_depends}}',
				'depends_id'
			);
		}
		
		if (is_null($table = $this->db->getTableSchema('comps_in_services'))) {
			$this->createTable('comps_in_services', [
				'id' => $this->primaryKey()->comment('id'),
				'comps_id' => $this->integer()->notNull(),
				'services_id' => $this->integer()->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
			$this->createIndex(
				'{{%idx-comps_in_services-comps_id}}',
				'{{%comps_in_services}}',
				'comps_id'
			);
			
			$this->createIndex(
				'{{%idx-comps_in_services-services_id}}',
				'{{%comps_in_services}}',
				'services_id'
			);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('comps_in_services'))) $this->dropTable('comps_in_services');
		if (!is_null($table = $this->db->getTableSchema('services_depends'))) $this->dropTable('services_depends');
		if (!is_null($table = $this->db->getTableSchema('services'))) $this->dropTable('services');
	}
}
