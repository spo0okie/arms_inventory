<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/** Хранит правила тегирования по версиям пакетов в карточке ПО. */
class M261126120000CreateSoftVersionTagRules extends ArmsMigration
{
	/** Создаёт таблицу правил и индексы для поиска по ПО и тегу. */
	public function up()
	{
		$this->createTable('soft_version_tag_rules', [
			'id'=>$this->primaryKey(),
			'soft_id'=>$this->integer()->notNull(),
			'tag_id'=>$this->integer()->notNull(),
			'format'=>$this->string()->notNull(),
			// Имена полей в JSON задаёт конфигурация формата.
			'conditions'=>$this->text()->notNull(),
		]);
		$this->createIndex('idx-version-rule-soft', 'soft_version_tag_rules', 'soft_id');
		$this->createIndex('idx-version-rule-tag', 'soft_version_tag_rules', 'tag_id');
	}

	/** Удаляет таблицу правил. */
	public function down()
	{
		$this->dropTable('soft_version_tag_rules');
	}
}
