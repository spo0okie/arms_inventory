<?php


namespace app\tests\unit\models;

use Codeception\Test\Unit;
use Yii;
use yii\console\Exception as ConsoleException;

/**
 * Прогон всей цепочки миграций на пустой БД - одним процессом и одним соединением.
 *
 * Одним процессом - принципиально: часть проблем миграций живет не в схеме, а в
 * состоянии соединения (выключенный автокоммит, незакрытая транзакция) и переезжает
 * из миграции в миграцию. Прогон "по одной миграции на процесс" такие проблемы
 * самоизлечивает и потому ничего не ловит - ровно поэтому незакрытый AUTOCOMMIT в
 * m190101_100008_update8 прожил в проекте несколько лет и выстрелил только при
 * развертывании докер-образа с нуля (см. plans/bugs20260820.md).
 *
 * Миграции применяются по одной, а между ними проверяется состояние соединения -
 * так в отчете видно, ПОСЛЕ КАКОЙ миграции соединение испортилось, а не просто
 * "упало где-то в середине".
 *
 * @see \tests\unit\db\MigrationSqlHygieneTest сторож тех же граблей на уровне исходников
 */
class MigrationTest extends Unit
{
	/** Предохранитель от вечного цикла, если migrate/up перестанет двигать историю. */
	const MAX_STEPS = 1000;

	/**
	 * Хранимые процедуры и функции, которые обязаны существовать после миграций.
	 * Их создание идет мультистейтментом, а ошибки мультистейтмента PDO умеет терять
	 * (mysql:8 без log_bin_trust_function_creators - ошибка 1419), поэтому проверяем
	 * результат, а не факт "миграции отработали".
	 */
	const REQUIRED_ROUTINES = [
		'getplacepath'      => ['PROCEDURE', 'FUNCTION'],
		'getplacetop'       => ['PROCEDURE', 'FUNCTION'],
		'getServiceSegment' => ['PROCEDURE', 'FUNCTION'],
	];

	protected function setUp(): void
	{
		parent::setUp();

		\Helper\Database::dropYiiDb();

		// Создаем БД (если её нет)
		\Helper\Database::prepareYiiDb();
	}

	protected function tearDown(): void
	{
		// Удаляем временную БД после теста
		\Helper\Database::dropYiiDb();
		parent::tearDown();
	}

	public function testAllMigrationsApplyWithoutErrors()
	{
		$this->getModule('Yii2')->_reconfigure(['cleanup' => false]);
		try {
			// Миграция RBAC
			ob_start();
			$result=Yii::$app->runAction('migrate/up', [
				'migrationPath' => '@yii/rbac/migrations/',
				//миграции проекта прописаны в controllerMap консольного конфига и без
				//явного сброса применились бы прямо здесь - всей пачкой и без проверок
				'migrationNamespaces' => [],
				'interactive' => 0,
			]);
			$output = ob_get_clean();
			codecept_debug($output);

			// Если код дошёл сюда — миграции выполнились без ошибок
			$this->assertTrue($result==0, 'Применение миграций вернуло ошибку');

		} catch (ConsoleException $e) {
			// Если миграция упала — тест провален
			$this->fail("Миграция RBAC завершилась с ошибкой: " . $e->getMessage());
		} catch (\Throwable $e) {
			// Любая другая ошибка (например, проблема с БД)
			$this->fail("Ошибка во время выполнения миграций RBAC: " . $e->getMessage());
		}

		$this->applyMigrationsOneByOne();
		$this->assertRequiredRoutinesExist();
	}

	/**
	 * Применяет миграции проекта по одной, проверяя после каждой, что соединение
	 * осталось пригодным для следующей.
	 */
	private function applyMigrationsOneByOne()
	{
		$applied = $this->appliedMigrations();

		for ($step = 0; $step < self::MAX_STEPS; $step++) {
			try {
				ob_start();
				$result = Yii::$app->runAction('migrate/up', [
					1,
					'migrationNamespaces' => ['app\migrations'],
					'interactive' => 0,
				]);
				$output = ob_get_clean();
			} catch (\Throwable $e) {
				//сюда прилетает то, что миграция не поймала сама: например PDOException
				//"There is already an active transaction" из beginTransaction() в up()
				ob_end_clean();
				$this->fail("Ошибка во время выполнения миграций (после "
					. $this->lastApplied($applied) . "): " . $e->getMessage());
			}
			codecept_debug($output);

			$now = $this->appliedMigrations();
			$new = array_values(array_diff($now, $applied));

			$this->assertTrue($result == 0, "Применение миграций вернуло ошибку (после "
				. $this->lastApplied($applied) . "):\n" . $output);

			//история не сдвинулась - применять больше нечего
			if (!$new) return;

			$applied = $now;
			$this->assertConnectionIsClean(implode(', ', $new));
		}

		$this->fail('Превышен лимит шагов миграции (' . self::MAX_STEPS . ')');
	}

	/**
	 * Состояние соединения после миграции: автокоммит включен, транзакция закрыта.
	 *
	 * Иначе следующая же миграция с safeUp() (yii\db\Migration::up() открывает
	 * транзакцию сам) упадет с "There is already an active transaction" - драйвер
	 * PDO_MySQL отдает in_transaction из статуса сервера, а при выключенном
	 * автокоммите сервер считает транзакцию открытой.
	 *
	 * @param string $migration имя только что примененной миграции (для сообщения)
	 */
	private function assertConnectionIsClean($migration)
	{
		$db = Yii::$app->db;

		$autocommit = (string)$db->createCommand('SELECT @@autocommit')->queryScalar();
		$this->assertSame('1', $autocommit, "Миграция $migration оставила выключенным автокоммит соединения. "
			. "Автокоммит - свойство соединения, а соединение общее на весь процесс `yii migrate`: "
			. "следующая миграция с safeUp() упадет с 'There is already an active transaction'. "
			. "Почини: SET AUTOCOMMIT = 1 после COMMIT в сыром SQL миграции.");

		$this->assertFalse($db->pdo->inTransaction(),
			"Миграция $migration оставила незакрытую транзакцию (PDO::inTransaction()).");
	}

	/**
	 * Хранимые процедуры/функции создаются мультистейтментом, ошибки которого PDO
	 * умеет терять - проверяем не рапорт миграции, а результат.
	 */
	private function assertRequiredRoutinesExist()
	{
		$db = Yii::$app->db;
		$rows = $db->createCommand(
			'SELECT routine_name AS name, routine_type AS type FROM information_schema.routines '
			. 'WHERE routine_schema = DATABASE()'
		)->queryAll();

		$existing = [];
		foreach ($rows as $row) {
			//MySQL отдает имена колонок information_schema в верхнем регистре, MariaDB - в нижнем
			$row = array_change_key_case($row);
			$existing[strtolower($row['name'])][] = strtoupper($row['type']);
		}

		foreach (self::REQUIRED_ROUTINES as $name => $types) {
			foreach ($types as $type) {
				$this->assertContains($type, $existing[strtolower($name)] ?? [],
					"После миграций в БД нет $type $name. Скорее всего ошибка создания потерялась "
					. "в мультистейтменте (на mysql:8 без log_bin_trust_function_creators это ошибка 1419), "
					. "а миграция отрапортовала об успехе.");
			}
		}
	}

	/** Список примененных миграций (версии из таблицы истории). */
	private function appliedMigrations(): array
	{
		return Yii::$app->db->createCommand('SELECT version FROM {{%migration}}')->queryColumn();
	}

	/** Имя последней примененной миграции - для сообщений об ошибках. */
	private function lastApplied(array $applied): string
	{
		return $applied ? (string)end($applied) : 'начала цепочки';
	}

}
