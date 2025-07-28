<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавление групп пользователей
 */
class m190101_100005_update5 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (is_null($table = $this->db->getTableSchema('user_groups'))) {
			$this->createTable('user_groups', [
				'id' => $this->primaryKey()->comment('id'),
				'name' => $this->string(64)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'description' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'notebook' => $this->text()->Null()->append(' COLLATE utf8mb4_unicode_ci'),
				'ad_group' => $this->string(255)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'sync_time' => $this->timestamp()->Null(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
		}
		
		if (is_null($table = $this->db->getTableSchema('users_in_groups'))) {
			$this->createTable('users_in_groups', [
				'id' => $this->primaryKey()->comment('id'),
				'users_id' => $this->string(16)->notNull()->append(' COLLATE utf8mb4_unicode_ci'),
				'groups_id' => $this->integer()->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
			
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
	public function down()
	{
		if (!is_null($table = $this->db->getTableSchema('users_in_groups'))) $this->dropTable('users_in_groups');
		if (!is_null($table = $this->db->getTableSchema('user_groups'))) $this->dropTable('user_groups');
	}
}
