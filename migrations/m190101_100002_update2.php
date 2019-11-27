<?php

use yii\db\Migration;

/**
 * Добавление лиц ключи
 */
class m190101_100002_update2 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		/*
		 *
			CREATE TABLE `lic_keys` (
			  `id` int(11) NOT NULL COMMENT 'id',
			  `lic_items_id` int(11) NOT NULL COMMENT 'Закупка',
			  `key_text` mediumtext COLLATE utf8mb4_unicode_ci,
			  `comment` mediumtext COLLATE utf8mb4_unicode_ci
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			
			-- new table `lic_keys_in_arms`
			
			CREATE TABLE `lic_keys_in_arms` (
			  `id` int(11) NOT NULL,
			  `lic_keys_id` int(11) NOT NULL,
			  `arms_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		 */
		
		
		if (is_null($table=$this->db->getTableSchema('lic_keys'))) {
			$this->createTable('lic_keys',[
				'id'    => $this->primaryKey()->comment('id'),
				'lic_items_id'  => $this->integer()->notNull()->comment('Закупка'),
				'key_text'  => $this->text()->notNull()->comment('Наименование')->append(' COLLATE utf8mb4_unicode_ci'),
				'comment'=>$this->text()->comment('Комментарий')->append(' COLLATE utf8mb4_unicode_ci')
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

			$this->createIndex(
				'{{%idx-lic_keys-lic_items}}',
				'{{%lic_keys}}',
				'lic_items_id'
			);
			
			$this->addForeignKey(
				'{{%fk-lic_keys_lic_items}}',
				'{{%lic_keys}}',
				'lic_items_id',
				'{{%lic_items}}',
				'id',
				'RESTRICT'
			);
			
		}
		
		if (is_null($table=$this->db->getTableSchema('lic_keys_in_arms'))) {
			$this->createTable('lic_keys_in_arms',[
				'id'    => $this->primaryKey()->comment('id'),
				'lic_keys_id'  => $this->integer()->notNull(),
				'arms_id'  => $this->integer()->notNull(),
			],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

			$this->createIndex(
				'{{%idx-lic_keys_in_arms-lic_keys_id}}',
				'{{%lic_keys_in_arms}}',
				'lic_keys_id'
			);
			
			$this->createIndex(
				'{{%idx-lic_keys_in_arms-lic_arms_id}}',
				'{{%lic_keys_in_arms}}',
				'arms_id'
			);
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('lic_keys_in_arms'))) $this->dropTable('lic_keys_in_arms');
		if (!is_null($table=$this->db->getTableSchema('lic_keys'))) $this->dropTable('lic_keys');
	}
}
