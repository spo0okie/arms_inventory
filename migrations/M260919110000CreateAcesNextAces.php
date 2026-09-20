<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Транзит межсервисных связей: указатель «следующий хоп» между записями доступа
 * (docs/dev/access-chains.md, §4).
 *
 * Хоп связи — запись доступа (ACE): субъект → ресурс. Связь «сервис А ходит в
 * сервис В через прокси Б» документируется двумя ACE (А→Б, Б→В) и указателем
 * между ними. Многие-ко-многим: у одного ACE несколько типов доступа с разными
 * портами могут уходить в разные апстримы, а несколько входящих ACE сходятся в
 * один исходящий. Обратная сторона (prev) читается из той же таблицы.
 *
 * Без FK — конвенция junction-таблиц проекта; сирот чистит generic
 * deleteJunctionRows(). В журнал ACE добавляются колонки под обе стороны связи.
 */
class M260919110000CreateAcesNextAces extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		//createMany2ManyTable пересоздаёт таблицу — на повторном прогоне существующую не трогаем
		if (!$this->tableExists('aces_next_aces')) {
			$this->createMany2ManyTable('aces_next_aces', ['aces_id', 'next_aces_id']);
		}
		$this->addColumnIfNotExists('aces_history', 'next_aces_ids', $this->text());
		$this->addColumnIfNotExists('aces_history', 'prev_aces_ids', $this->text());
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('aces_history', 'prev_aces_ids');
		$this->dropColumnIfExists('aces_history', 'next_aces_ids');
		$this->dropTableIfExists('aces_next_aces');
	}
}
