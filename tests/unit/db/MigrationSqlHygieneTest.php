<?php

namespace tests\unit\db;

use app\migrations\arms\ArmsMigration;
use Codeception\Test\Unit;

/**
 * Сторож гигиены сырого SQL в миграциях.
 *
 * Обе проверки родились из разбора установки "с нуля" в докере (см. plans/bugs20260820.md):
 * на живых инсталляциях цепочка миграций уже применена, поэтому ошибки в ней всплывают
 * только при развертывании чистой БД, и всплывают там, где их никто не ждет.
 *
 * 1) Незакрытый AUTOCOMMIT. Сырой SQL вида `SET AUTOCOMMIT=0; START TRANSACTION; ... COMMIT;`
 *    выключает автокоммит на все соединение, а не на одну миграцию. Соединение живет
 *    до конца процесса `yii migrate`, поэтому первая же следующая миграция с safeUp()
 *    (yii\db\Migration::up() открывает транзакцию сам) падает с PDOException
 *    "There is already an active transaction" — драйвер PDO_MySQL отдает in_transaction
 *    из статуса сервера, а при выключенном автокоммите сервер считает транзакцию открытой.
 *
 * 2) Склеенные мультистейтменты. PDO с эмуляцией prepare отправляет всю строку одним
 *    запросом, но проверяет результат только первого стейтмента: ошибки второго и далее
 *    остаются в непрочитанных rowset'ах и молча теряются. Так на mysql:8 без
 *    log_bin_trust_function_creators потерялась ошибка 1419 на `CREATE FUNCTION getplacepath`
 *    — миграции "прошли успешно", а первый же экран с местами упал 500-й.
 *    ArmsMigration::execute() дочитывает rowset'ы до конца, поэтому мультистейтменты
 *    допустимы только в миграциях, унаследованных от нее.
 *
 * Тест читает только исходники — БД не нужна.
 *
 * @see \app\migrations\arms\ArmsMigration::execute()
 * @see \tests\unit\db\ArmsMigrationExecuteTest
 */
class MigrationSqlHygieneTest extends Unit
{
	/** Каталог миграций проекта (без подпапки arms/ с базовыми классами). */
	private function migrationFiles(): array
	{
		$files = glob(dirname(__DIR__, 3) . '/migrations/*.php');
		$this->assertNotEmpty($files, 'Не найдены файлы миграций');
		return $files;
	}

	/**
	 * Вытаскивает из исходника все SQL-литералы: тела heredoc'ов и обычные строки,
	 * начинающиеся с SQL-ключевого слова. Экранирование не разворачиваем — для наших
	 * проверок (наличие подстрок и точек с запятой) это несущественно.
	 *
	 * @return string[]
	 */
	private function sqlLiterals(string $file): array
	{
		$literals = [];
		$heredoc = null;

		foreach (token_get_all(file_get_contents($file)) as $token) {
			if (!is_array($token)) continue;

			if ($token[0] === T_START_HEREDOC) { $heredoc = ''; continue; }
			if ($token[0] === T_END_HEREDOC) {
				if ($heredoc !== null) $literals[] = $heredoc;
				$heredoc = null;
				continue;
			}
			if ($heredoc !== null) {
				if ($token[0] === T_ENCAPSED_AND_WHITESPACE) $heredoc .= $token[1];
				continue;
			}
			if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
				$text = substr($token[1], 1, -1);
				if ($this->looksLikeSql($text)) $literals[] = $text;
			}
		}

		return $literals;
	}

	/** Похоже ли содержимое строки на SQL-запрос (чтобы не ловить сообщения и имена). */
	private function looksLikeSql(string $text): bool
	{
		//срезаем ведущие SQL-комментарии
		$text = preg_replace('/^\s*(?:(?:--|#)[^\n]*\n)+/', '', $text);
		return (bool)preg_match(
			'/^\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|SET|CALL|TRUNCATE|GRANT|RENAME)\b/i',
			(string)$text
		);
	}

	/**
	 * Содержит ли SQL больше одного стейтмента. Строковые литералы и комментарии
	 * выкусываем, завершающую точку с запятой не считаем.
	 *
	 * Тела процедур (BEGIN ... END) тоже попадают под критерий — и это правильно:
	 * их всегда шлют вместе с DROP/SET, а разбирать диалект ради точности незачем,
	 * лечение одно и то же — унаследоваться от ArmsMigration.
	 */
	private function isMultiStatement(string $sql): bool
	{
		$clean = preg_replace("/'[^']*'/", "''", $sql);
		$clean = preg_replace('/(?:--|#)[^\n]*/', '', (string)$clean);
		$clean = rtrim(trim((string)$clean), "; \t\n\r");
		return strpos($clean, ';') !== false;
	}

	/**
	 * Выключил автокоммит — включи обратно, иначе поедет вся остальная цепочка миграций.
	 */
	public function testAutocommitIsRestored()
	{
		foreach ($this->migrationFiles() as $file) {
			foreach ($this->sqlLiterals($file) as $sql) {
				if (!preg_match_all('/AUTOCOMMIT\s*=\s*0/i', $sql, $m, PREG_OFFSET_CAPTURE)) continue;

				$offOffset = end($m[0])[1];
				$restored = preg_match_all('/AUTOCOMMIT\s*=\s*1/i', $sql, $on, PREG_OFFSET_CAPTURE)
					&& end($on[0])[1] > $offOffset;

				$this->assertTrue($restored, basename($file) . ": SQL выключает автокоммит (SET AUTOCOMMIT=0), "
					. "но не возвращает его (SET AUTOCOMMIT=1 после COMMIT). Автокоммит - свойство соединения, "
					. "а соединение общее на весь процесс `yii migrate`: следующая же миграция с safeUp() упадет "
					. "с 'There is already an active transaction'.");
			}
		}
	}

	/**
	 * Мультистейтмент разрешен только через ArmsMigration::execute(), которая дочитывает
	 * rowset'ы и не дает ошибкам второго и последующих стейтментов потеряться.
	 */
	public function testMultiStatementSqlGoesThroughArmsMigration()
	{
		foreach ($this->migrationFiles() as $file) {
			$multi = array_filter($this->sqlLiterals($file), [$this, 'isMultiStatement']);
			if (!$multi) continue;

			$class = 'app\\migrations\\' . basename($file, '.php');
			$this->assertTrue(class_exists($class), "Не удалось загрузить класс миграции $class");
			$this->assertTrue(
				is_subclass_of($class, ArmsMigration::class),
				basename($file) . ": миграция шлет в execute() несколько стейтментов одной строкой, "
				. "но унаследована не от ArmsMigration. PDO проверяет результат только первого стейтмента, "
				. "ошибки остальных молча теряются (так пропала ошибка 1419 на CREATE FUNCTION getplacepath). "
				. "Почини: extends ArmsMigration - ее execute() дочитывает rowset'ы до конца."
			);
		}
	}
}
