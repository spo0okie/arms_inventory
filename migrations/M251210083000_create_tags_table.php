<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Создание таблицы tags для системы тегов
 */
class M251210083000_create_tags_table extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		if ($this->tableExists('tags')) return true;
        $this->createTable('tags', [
            'id' => $this->primaryKey(),
            'name' => $this->string(32)->notNull()->comment('Название тега'),
            'slug' => $this->string(48)->notNull()->unique()->comment('Уникальный идентификатор'),
            'color' => $this->string(7)->notNull()->defaultValue('#007bff')->comment('Цвет фона в HEX формате'),
            'description' => $this->string(255)->null()->comment('Описание назначения тега'),
            'usage_count' => $this->integer()->notNull()->defaultValue(0)->comment('Количество использований'),
            'archived' => $this->boolean()->notNull()->defaultValue(0)->comment('Признак архивирования'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'updated_at' => $this->timestamp()->null()->comment('Дата последнего изменения'),
            'updated_by' => $this->string(32)->null()->comment('Автор последних изменений (username)'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="Теги для категоризации объектов"');
        
        // Индексы
        $this->createIndex('idx-tags-slug', 'tags', 'slug');
        $this->createIndex('idx-tags-usage_count', 'tags', 'usage_count');
        $this->createIndex('idx-tags-archived', 'tags', 'archived');
        $this->createIndex('idx-tags-name', 'tags', 'name');

    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropTableIfExists('tags');
    }
}