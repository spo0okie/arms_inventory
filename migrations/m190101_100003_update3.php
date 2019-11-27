<?php

use yii\db\Migration;

/**
 * Добавление лиц ключи
 */
class m190101_100003_update3 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		
		if (is_null($table=$this->db->getTableSchema('orgs'))) $this->createTable('orgs',[
			'id'    => $this->primaryKey()->comment('id'),
			'name'  => $this->string(128)->notNull()->comment('Наименование')->append(' COLLATE utf8mb4_unicode_ci'),
			'short'  => $this->string(16)->notNull()->comment('Короткое имя')->append(' COLLATE utf8mb4_unicode_ci'),
			'comment'=>$this->text()->comment('Комментарий')->append(' COLLATE utf8mb4_unicode_ci')
		],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		if (!is_null($table=$this->db->getTableSchema('orgs'))) $this->dropTable('orgs');
	}
}
