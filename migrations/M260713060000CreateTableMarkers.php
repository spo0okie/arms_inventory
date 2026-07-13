<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Создание таблицы markers — цветовые маркеры объектов (issue #141).
 *
 * Маркер = фон (обязателен, универсальный канал) + опциональные
 * цвет текста (NULL = автоконтраст), цвет и стиль рамки.
 */
class M260713060000CreateTableMarkers extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if ($this->tableExists('markers')) return true;
		$this->createTable('markers', [
			'id' => $this->primaryKey(),
			'name' => $this->string(64)->notNull()->comment('Название маркера'),
			'color' => $this->string(7)->notNull()->comment('Цвет фона в HEX формате'),
			'text_color' => $this->string(7)->null()->comment('Цвет текста (NULL = автоконтраст к фону)'),
			'border_color' => $this->string(7)->null()->comment('Цвет рамки (NULL = без рамки)'),
			'border_style' => $this->string(8)->null()->comment('Стиль рамки: solid/dashed'),
			'comment' => $this->string(255)->null()->comment('Пояснение, когда применяется этот маркер'),
			'archived' => $this->boolean()->notNull()->defaultValue(0)->comment('Признак архивирования'),
			'updated_at' => $this->timestamp()->null()->comment('Дата последнего изменения'),
			'updated_by' => $this->string(32)->null()->comment('Автор последних изменений (username)'),
		]);

		$this->createIndex('idx-markers-name', 'markers', 'name', true);
		$this->createIndex('idx-markers-archived', 'markers', 'archived');
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('markers');
	}
}
