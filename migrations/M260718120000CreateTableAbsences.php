<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Создание таблиц absences и absences_history — отсутствия сотрудников
 * (отпуска, больничные, командировки и т.п.), см. plans/ARMS-absences-spec.md.
 *
 * ARMS выступает хабом-агрегатором: отсутствия приходят из нескольких источников
 * (sap|c1|manual) и выгружаются наружу (SAPsync → Bitrix). Организация у отсутствия
 * не хранится — она определяется сотрудником (users.org_id).
 *
 * Ключи/индексы:
 *  - уникальный (source, external_id) — идемпотентный upsert из источника;
 *    записи ручного ввода (external_id IS NULL) под ограничение не попадают
 *    (в MySQL несколько NULL в UNIQUE-индексе допускаются);
 *  - индексы user_id, (date_from, date_to) — под выборку по сотруднику
 *    и поиск пересечений периодов.
 *
 * FK на уровне БД не создаём: в ARMS скалярные связи описываются через
 * linksSchema модели (см. Absences::$linksSchema), явные foreign key заводятся
 * только для M2M-таблиц (createMany2ManyTable). Индексы под связи присутствуют.
 */
class M260718120000CreateTableAbsences extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (!$this->tableExists('absences')) {
			$this->createTable('absences', [
				'id' => $this->primaryKey(),
				'user_id' => $this->integer()->notNull()->comment('Сотрудник (конкретное трудоустройство), users.id'),
				'type' => $this->string(16)->notNull()->comment('Нормализованный тип отсутствия (код совпадает с XML_ID в Bitrix)'),
				'date_from' => $this->date()->notNull()->comment('Начало периода отсутствия'),
				'date_to' => $this->date()->notNull()->comment('Конец периода отсутствия'),
				'comment' => $this->string(255)->null()->comment('Текст отсутствия из источника (SAP Abstext)'),
				'source' => $this->string(8)->notNull()->comment('Источник записи: sap|c1|manual'),
				'external_id' => $this->string(64)->null()->comment('Натуральный ключ записи в источнике; для manual — NULL'),
				'updated_at' => $this->timestamp()->null()->comment('Дата последнего изменения'),
				'updated_by' => $this->string(32)->null()->comment('Автор последних изменений (username)'),
			]);

			$this->createIndex('idx-absences-user_id', 'absences', 'user_id');
			$this->createIndex('idx-absences-period', 'absences', ['date_from', 'date_to']);
			//идемпотентность upsert из источника (manual с NULL external_id не конфликтует)
			$this->createIndex('idx-absences-source-external', 'absences', ['source', 'external_id'], true);
		}

		if (!$this->tableExists('absences_history')) {
			$this->createTable('absences_history', [
				'id' => $this->primaryKey(),
				'master_id' => $this->integer(),
				'user_id' => $this->integer(),
				'type' => $this->string(16),
				'date_from' => $this->date(),
				'date_to' => $this->date(),
				'comment' => $this->string(255),
				'source' => $this->string(8),
				'external_id' => $this->string(64),
				'updated_at' => $this->timestamp()->null(),
				'updated_by' => $this->string(32),
				'updated_comment' => $this->string(),
				'changed_attributes' => $this->text(),
			]);

			$this->createIndex('idx-absences_history-master_id', 'absences_history', 'master_id');
			$this->createIndex('idx-absences_history-updated_at', 'absences_history', 'updated_at');
			$this->createIndex('idx-absences_history-updated_by', 'absences_history', 'updated_by');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('absences_history');
		$this->dropTableIfExists('absences');
	}
}
