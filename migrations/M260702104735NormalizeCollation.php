<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Нормализация кодировки/коллации всей БД к стандарту проекта
 * (utf8mb4 / utf8mb4_unicode_ci).
 *
 * Зачем: коллации в инсталляциях "уплыли" — часть столбцов оказалась в
 * utf8mb4_general_ci / utf8mb3_* / серверном дефолте (utf8mb4_0900_ai_ci на
 * MySQL 9, utf8mb4_uca1400_ai_ci на MariaDB 11). Из-за разнородности поисковые
 * CONCAT-выражения в *Search-моделях падают с ошибкой
 * 1267 "Illegal mix of collations ... for operation 'like'".
 *
 * CONVERT TO CHARACTER SET чинит проблему независимо от исходной коллации
 * столбца и заодно переводит старый utf8mb3 в utf8mb4. Данные не меняются —
 * меняется только объявленная кодировка/коллация.
 *
 * Идемпотентна: повторный прогон приводит уже нормализованные таблицы к тому же
 * состоянию. Каждая таблица физически перестраивается (ALTER ... CONVERT берёт
 * блокировку) — на больших БД запускать в окно обслуживания.
 *
 * @see \app\migrations\arms\ArmsMigration::CHARSET
 * @see tests\unit\db\CollationConsistencyTest
 */
class M260702104735NormalizeCollation extends ArmsMigration
{
	public function up()
	{
		$dbName = $this->db->createCommand('SELECT DATABASE()')->queryScalar();

		// 1. Дефолт самой БД — чтобы новые таблицы/столбцы наследовали стандарт.
		$this->execute(
			"ALTER DATABASE `$dbName` CHARACTER SET " . static::CHARSET . " COLLATE " . static::COLLATION
		);

		// 2. Все базовые таблицы текущей БД (включая служебные Yii — тоже приводим к стандарту).
		$tables = $this->db->createCommand(
			"SELECT table_name FROM information_schema.tables
			  WHERE table_schema = :db AND table_type = 'BASE TABLE'",
			[':db' => $dbName]
		)->queryColumn();

		// manufacturers_dict.word не должна содержать пустые/NULL/пробельные значения.
		// Дропим UNIQUE индекс перед очисткой и конвертацией, чтобы не нарушить constraint.
		if (in_array('manufacturers_dict', $tables)) {
			echo "    > drop unique index on manufacturers_dict.word\n";
			$this->execute("ALTER TABLE `manufacturers_dict` DROP INDEX `word`");

			echo "    > cleanup empty values in manufacturers_dict.word\n";
			$this->execute("DELETE FROM `manufacturers_dict` WHERE `word` = '' OR `word` IS NULL OR TRIM(`word`) = ''");
		}

		// FK-столбцы должны совпадать по коллации с родительскими. На время
		// конвертации гасим проверку, иначе промежуточное состояние (ребёнок
		// сконвертирован, родитель ещё нет) даст ошибку несовместимости FK.
		$this->execute('SET FOREIGN_KEY_CHECKS = 0');
		try {
			foreach ($tables as $table) {
				echo "    > convert $table\n";
				$this->convertTableToCollation($table);
			}
		} finally {
			$this->execute('SET FOREIGN_KEY_CHECKS = 1');
		}

		// Пересоздаём UNIQUE индекс на manufacturers_dict.word после конвертации
		if (in_array('manufacturers_dict', $tables)) {
			echo "    > recreate unique index on manufacturers_dict.word\n";
			$this->execute("ALTER TABLE `manufacturers_dict` ADD UNIQUE KEY `word` (`word`)");
		}
	}

	public function down()
	{
		echo static::class . ": откат нормализации коллации не поддерживается.\n";
		return false;
	}
}
