<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Добавление серверов
 */
class m190101_100009_update9 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$table=$this->db->getTableSchema('{{%tech_types}}');
		
		if (!isset($table->columns['comment_name'])) {
			$this->addColumn('tech_types','comment_name',$this->string(32)->null()->append('COLLATE utf8mb4_unicode_ci'));
		}
		if (!isset($table->columns['comment_hint'])) {
			$this->addColumn('tech_types','comment_hint',$this->string(128)->null()->append('COLLATE utf8mb4_unicode_ci'));
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$table=$this->db->getTableSchema('{{%tech_types}}');
		
		if (isset($table->columns['comment_name'])) {
			$this->dropColumn('tech_types','comment_name');
		}
		if (isset($table->columns['comment_hint'])) {
			$this->dropColumn('tech_types','comment_hint');
		}
	}
}
