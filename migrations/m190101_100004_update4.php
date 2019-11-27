<?php

use yii\db\Migration;

/**
 * Добавление услуг ИТ
 */
class m190101_100004_update4 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		/*
		 *
			CREATE TABLE `services` (
			  `id` int(11) NOT NULL,
			  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			  `description` mediumtext COLLATE utf8mb4_unicode_ci,
			  `links` mediumtext COLLATE utf8mb4_unicode_ci,
			  `is_end_user` tinyint(1) NOT NULL,
			  `user_group_id` int(11) DEFAULT NULL,
			  `sla_id` int(11) DEFAULT NULL,
			  `notebook` mediumtext COLLATE utf8mb4_unicode_ci
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			
			-- new table `services_depends`
			
			CREATE TABLE `services_depends` (
			  `id` int(11) NOT NULL,
			  `service_id` int(11) NOT NULL,
			  `depends_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		
			CREATE TABLE `comps_in_services` (
			  `id` int(11) NOT NULL,
			  `comps_id` int(11) NOT NULL,
			  `services_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			

		 */
		
		if (is_null($table=$this->db->getTableSchema('services'))) {
			$this->createTable('services',[
				'id'    => $this->primaryKey()->comment('id'),
				'name'  => $this->string(64)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'description'  => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'links'  => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'is_end_user'  => $this->boolean()->notNull(),
				'user_group_id'  => $this->integer()->Null(),
				'sla_id'  => $this->integer()->Null(),
				'notebook'=>$this->text()->append(' COLLATE utf8mb4_unicode_ci')
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
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
		
		if (is_null($table=$this->db->getTableSchema('services_depends'))) {
			$this->createTable('services_depends',[
				'id'    => $this->primaryKey()->comment('id'),
				'service_id'    => $this->integer()->notNull(),
				'depends_id'    => $this->integer()->notNull(),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
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
		
		if (is_null($table=$this->db->getTableSchema('comps_in_services'))) {
			$this->createTable('comps_in_services',[
				'id'    => $this->primaryKey()->comment('id'),
				'comps_id'    => $this->integer()->notNull(),
				'services_id'    => $this->integer()->notNull(),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
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
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('comps_in_services'))) $this->dropTable('comps_in_services');
		if (!is_null($table=$this->db->getTableSchema('services_depends'))) $this->dropTable('services_depends');
		if (!is_null($table=$this->db->getTableSchema('services'))) $this->dropTable('services');
	}
}
