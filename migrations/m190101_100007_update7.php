<?php

use yii\db\Migration;

/**
 * Добавление серверов
 */
class m190101_100007_update7 extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->alterColumn('{{%arms}}','updated_at',$this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'));
		$this->execute("UPDATE `arms` SET updated_at=NULL where updated_at<'1960-01-01 00:00:00'");

		$this->addColumn('{{%arms}}','is_server',$this->boolean());
		
		
		$this->createIndex(
			'{{%idx-arms-is_server}}',
			'{{%arms}}',
			'is_server'
		);
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->alterColumn('{{%arms}}','updated_at',$this->timestamp()->null()->defaultValue(null));
		$this->execute("UPDATE `arms` SET updated_at=NULL where updated_at<'1960-01-01 00:00:00'");

		$table=$this->db->getTableSchema('{{%arms}}');
		if (isset($table->columns['is_server'])) $this->dropColumn('{{%arms}}', 'is_server');
	}
}
