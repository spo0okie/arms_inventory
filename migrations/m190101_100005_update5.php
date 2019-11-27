<?php

use yii\db\Migration;

/**
 * Добавление лиц ключи
 */
class m190101_100005_update5 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		/*
		 *
			CREATE TABLE `users_in_groups` (
			  `id` int(11) NOT NULL,
			  `users_id` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			  `groups_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			
			-- new table `user_groups`
			
			CREATE TABLE `user_groups` (
			  `id` int(11) NOT NULL,
			  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			  `description` mediumtext COLLATE utf8mb4_unicode_ci,
			  `notebook` mediumtext COLLATE utf8mb4_unicode_ci,
			  `ad_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			  `sync_time` timestamp NULL DEFAULT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		 */
		
		if (is_null($table=$this->db->getTableSchema('user_groups'))) {
			$this->createTable('user_groups',[
				'id'    => $this->primaryKey()->comment('id'),
				'name'  => $this->string(64)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'description'  => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'notebook'  => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'ad_group'  => $this->string(255)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'sync_time'  => $this->timestamp()->Null(),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
		}
		
		if (is_null($table=$this->db->getTableSchema('users_in_groups'))) {
			$this->createTable('users_in_groups',[
				'id'    => $this->primaryKey()->comment('id'),
				'users_id'    => $this->string(16)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'groups_id'    => $this->integer()->notNull(),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
			$this->createIndex(
				'{{%idx-users_in_groups-users_id}}',
				'{{%users_in_groups}}',
				'users_id'
			);
			
			$this->createIndex(
				'{{%idx-users_in_groups-groups_id}}',
				'{{%users_in_groups}}',
				'groups_id'
			);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('users_in_groups'))) $this->dropTable('users_in_groups');
		if (!is_null($table=$this->db->getTableSchema('user_groups'))) $this->dropTable('user_groups');
	}
}
