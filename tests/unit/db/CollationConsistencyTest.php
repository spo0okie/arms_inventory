<?php

namespace tests\unit\db;

use Codeception\Test\Unit;
use Yii;

/**
 * Сторож консистентности коллаций БД (профилактика ошибки 1267
 * "Illegal mix of collations ... for operation 'like'").
 *
 * Причина ошибки — разнородные коллации столбцов: поисковые выражения в
 * *Search-моделях склеивают через CONCAT столбцы из разных таблиц
 * (например app\models\CompsSearch::search -> concat(domains.name, comps.name, ...)),
 * и если у столбцов разные коллации, результат схлопывается в (utf8mb4_bin, NONE),
 * после чего LIKE против литерала (коллация соединения) падает с 1267.
 *
 * Коллации "уплывают", потому что сервер БД подставляет свой дефолт, когда
 * столбец/таблица создаётся без явного COLLATE, а дефолт разный на разных
 * серверах (MySQL 9 — utf8mb4_0900_ai_ci, MariaDB 11 — utf8mb4_uca1400_ai_ci,
 * старые дампы — utf8mb4_general_ci / utf8mb3_*). Канон проекта — utf8mb4_unicode_ci
 * (жёстко прописан во всех createTable initial-миграции).
 *
 * Тест читает information_schema текущей БД (arms_test, см. unit.suite.yml)
 * и падает, если что-то не в каноне. Это ловит дрейф на любой инсталляции
 * и на новых миграциях. БД только читается — данные не трогаются.
 */
class CollationConsistencyTest extends Unit
{
	/** Канон проекта. */
	const CHARSET   = 'utf8mb4';
	const COLLATION = 'utf8mb4_unicode_ci';

	/** @var \UnitTester */
	protected $tester;

	private function dbName(): string
	{
		return (string)Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
	}

	/**
	 * Дефолтная коллация самой БД: чтобы новые таблицы/столбцы, созданные без
	 * явного COLLATE, наследовали канон, а не серверный дефолт.
	 */
	public function testDatabaseDefaultCollation()
	{
		$db = $this->dbName();
		$collation = Yii::$app->db->createCommand(
			'SELECT default_collation_name FROM information_schema.schemata WHERE schema_name = :db',
			[':db' => $db]
		)->queryScalar();

		$this->assertSame(
			self::COLLATION,
			$collation,
			"БД `$db`: дефолтная коллация `$collation`, ожидается `" . self::COLLATION . "`. "
			. "Почини: ALTER DATABASE `$db` CHARACTER SET " . self::CHARSET . " COLLATE " . self::COLLATION
		);
	}

	/**
	 * Дефолтная коллация каждой таблицы: чтобы addColumn() без явного COLLATE
	 * наследовал канон таблицы.
	 */
	public function testAllTablesUseCanonicalCollation()
	{
		$db = $this->dbName();
		// Явные алиасы в нижнем регистре: MySQL отдаёт имена колонок
		// information_schema в верхнем регистре, MariaDB — в нижнем.
		$rows = Yii::$app->db->createCommand(
			"SELECT table_name AS tbl, table_collation AS coll
			   FROM information_schema.tables
			  WHERE table_schema = :db
			    AND table_type = 'BASE TABLE'
			    AND table_collation <> :coll
			  ORDER BY table_name",
			[':db' => $db, ':coll' => self::COLLATION]
		)->queryAll();

		$offenders = array_map(
			fn($r) => "{$r['tbl']} = {$r['coll']}",
			$rows
		);

		$this->assertSame([], $offenders, $this->report(
			count($offenders) . " таблиц(ы) с неканоничной дефолтной коллацией (ожидается " . self::COLLATION . "):",
			$offenders
		));
	}

	/**
	 * Все строковые столбцы должны быть в каноничной коллации и charset.
	 * Именно расхождение здесь и приводит к 1267 в CONCAT-поиске.
	 */
	public function testAllStringColumnsUseCanonicalCollation()
	{
		$db = $this->dbName();
		$rows = Yii::$app->db->createCommand(
			"SELECT c.table_name AS tbl, c.column_name AS col,
			        c.character_set_name AS charset, c.collation_name AS coll
			   FROM information_schema.columns c
			   JOIN information_schema.tables t
			     ON t.table_schema = c.table_schema
			    AND t.table_name = c.table_name
			    AND t.table_type = 'BASE TABLE'
			  WHERE c.table_schema = :db
			    AND c.collation_name IS NOT NULL
			    AND c.collation_name <> :coll
			  ORDER BY c.table_name, c.column_name",
			[':db' => $db, ':coll' => self::COLLATION]
		)->queryAll();

		$offenders = array_map(
			fn($r) => "{$r['tbl']}.{$r['col']} = {$r['charset']}/{$r['coll']}",
			$rows
		);

		$this->assertSame([], $offenders, $this->report(
			count($offenders) . " столбц(ов) с неканоничной коллацией (ожидается " . self::COLLATION . "):",
			$offenders
		));
	}

	/**
	 * Компактное сообщение об ошибке: заголовок + до 50 нарушителей.
	 */
	private function report(string $header, array $offenders): string
	{
		$shown = array_slice($offenders, 0, 50);
		$tail = count($offenders) - count($shown);
		return $header . "\n  " . implode("\n  ", $shown)
			. ($tail > 0 ? "\n  ... и ещё $tail" : '');
	}
}
