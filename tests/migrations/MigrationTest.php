<?php

namespace app\tests\unit\models;

use Codeception\Test\Unit;
use Yii;
use yii\console\Exception as ConsoleException;

class MigrationTest extends Unit
{
	/** @var \yii\db\Connection */
	private $rootDb;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		// Подключаем конфиг временной БД
		$this->rootDb = Yii::$app->get('db_root');
		
		// Создаем БД (если её нет)
		$this->createMigrationDatabase();
	}
	
	protected function tearDown(): void
	{
		// Удаляем временную БД после теста
		$this->dropMigrationDatabase();
		
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
		
		try {
			// Запускаем миграции (неинтерактивно)
			ob_start();
			$result=Yii::$app->runAction('migrate/up', [
				//'migrationPath' => '@app/migrations',
				'interactive' => 0,
			]);
			$output = ob_get_clean();
			codecept_debug($output);
			
			// Если код дошёл сюда — миграции выполнились без ошибок
			$this->assertTrue($result==0, 'Применение миграций вернуло ошибку');
			
		} catch (ConsoleException $e) {
			// Если миграция упала — тест провален
			$this->fail("Миграция завершилась с ошибкой: " . $e->getMessage());
		} catch (\Throwable $e) {
			// Любая другая ошибка (например, проблема с БД)
			$this->fail("Ошибка во время выполнения миграций: " . $e->getMessage());
		}
	}
	
	private function createMigrationDatabase()
	{
		codecept_debug('Creating temporary migration database...');
		$this->rootDb->open();
		$this->rootDb->createCommand("CREATE DATABASE IF NOT EXISTS yii2_migrations_test")->execute();
		$this->rootDb->close();
		codecept_debug('Complete');
	}
	
	private function dropMigrationDatabase()
	{
		codecept_debug('Dropping temporary migration database...');
		Yii::$app->db->close(); // Закрываем подключение к основной БД, чтобы не было конфликтов
		$this->rootDb->open();
		$this->rootDb->createCommand("DROP DATABASE IF EXISTS yii2_migrations_test")->execute();
		$this->rootDb->close();
		codecept_debug('Complete');
	}
	
}