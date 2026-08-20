<?php

namespace tests\unit\db;

use app\migrations\arms\ArmsMigration;
use Codeception\Test\Unit;
use Yii;
use yii\db\Migration;

/**
 * Проверяет, что ArmsMigration::execute() не теряет ошибки мультистейтментов.
 *
 * Ловушка PDO: при эмуляции prepare (по умолчанию для MySQL) вся строка уходит на
 * сервер одним запросом, но результат читается только у первого стейтмента. Ошибка
 * второго и последующих остается в непрочитанном rowset'е и до приложения не доходит:
 * миграция рапортует об успехе, а объект БД не создан. Именно так на mysql:8 без
 * log_bin_trust_function_creators потерялась ошибка 1419 на CREATE FUNCTION
 * getplacepath (см. plans/bugs20260820.md).
 *
 * Тест только читает БД (SELECT из несуществующей таблицы) - данные не трогает.
 *
 * @see \app\migrations\arms\ArmsMigration::execute()
 * @see \tests\unit\db\MigrationSqlHygieneTest
 */
class ArmsMigrationExecuteTest extends Unit
{
	/** Мультистейтмент, у которого падает ВТОРОЙ стейтмент. */
	const BROKEN_SQL = 'SELECT 1; SELECT * FROM no_such_table_for_test';

	/** @var \UnitTester */
	protected $tester;

	private function migration(string $class): Migration
	{
		return new $class(['db' => Yii::$app->db, 'compact' => true]);
	}

	/**
	 * Штатное поведение Yii: ошибка второго стейтмента теряется. Фиксируем его,
	 * чтобы было видно, от чего именно защищает ArmsMigration (и чтобы тест
	 * заметил, если однажды это починят в самом Yii/PDO).
	 */
	public function testPlainMigrationSwallowsTrailingStatementErrors()
	{
		$migration = $this->migration(Migration::class);

		ob_start();
		$migration->execute(self::BROKEN_SQL);
		ob_end_clean();

		$this->assertTrue(true, 'yii\db\Migration::execute() не заметил ошибки второго стейтмента');
	}

	/**
	 * А ArmsMigration - замечает.
	 */
	public function testArmsMigrationSurfacesTrailingStatementErrors()
	{
		$migration = $this->migration(ArmsMigration::class);

		$thrown = null;
		ob_start();
		try {
			$migration->execute(self::BROKEN_SQL);
		} catch (\Throwable $e) {
			$thrown = $e;
		}
		ob_end_clean();

		$this->assertNotNull($thrown, 'ArmsMigration::execute() пропустил ошибку второго стейтмента: '
			. 'миграция отрапортует об успехе, а объект БД создан не будет');
		$this->assertStringContainsString('no_such_table_for_test', $thrown->getMessage(),
			'В сообщении об ошибке не видно, какой именно стейтмент упал');
	}

	/**
	 * Обычный (одиночный) запрос сквозь ту же обертку работает как раньше.
	 */
	public function testSingleStatementStillWorks()
	{
		$migration = $this->migration(ArmsMigration::class);

		ob_start();
		$migration->execute('SET @arms_execute_probe = 1');
		ob_end_clean();

		$this->assertSame('1', (string)Yii::$app->db->createCommand('SELECT @arms_execute_probe')->queryScalar());
	}
}
