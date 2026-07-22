<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Добавляет в scans привязку к сотруднику — колонка users_id (+ индекс),
 * см. plans/ARMS-employee-photos-spec.md.
 *
 * Фото сотрудника хранится как scan, привязанный к пользователю: переиспользует
 * готовый механизм scans (файл + fileDate + REST-скачивание + межинстансовая
 * синхронизация). «Портрет» для SAPsync — последнее по дате изображение
 * сотрудника (отдельного флага is_portrait не заводим).
 *
 * FK на уровне БД не создаём — конвенция ARMS: скалярные связи через linksSchema
 * модели, явные foreign key только у M2M-таблиц. Индекс под фильтрацию есть.
 */
class M260718130000ScansAddUsersId extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('scans', 'users_id', $this->integer()->null()->comment('Сотрудник, к которому прикреплён скан (фото)'), true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropIndexIfExists('idx-scans-users_id', 'scans');
		$this->dropColumnIfExists('scans', 'users_id');
	}
}
