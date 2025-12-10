<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Создание таблицы tags_links для полиморфных связей тегов с объектами
 */
class m251210_083100_create_tags_links_table extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('tags_links', [
            'id' => $this->primaryKey(),
            'tag_id' => $this->integer()->notNull()->comment('ID тега'),
            'model_class' => $this->string(255)->notNull()->comment('Класс модели (полное имя с namespace)'),
            'model_id' => $this->integer()->notNull()->comment('ID объекта модели'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания связи'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="Связи тегов с объектами (полиморфная связь)"');
        
        // Индексы для быстрого поиска
        $this->createIndex('idx-tags_links-tag_id', 'tags_links', 'tag_id');
        $this->createIndex('idx-tags_links-model', 'tags_links', ['model_class', 'model_id']);
        $this->createIndex('idx-tags_links-composite', 'tags_links', ['tag_id', 'model_class', 'model_id']);
		
        // Уникальный составной индекс - один тег не может быть назначен одному объекту дважды
        $this->createIndex(
            'unique-tags_links-tag_model',
            'tags_links',
            ['tag_id', 'model_class', 'model_id'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTableIfExists('tags_links');
    }
}