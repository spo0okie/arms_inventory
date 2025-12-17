<?php


namespace app\tests\unit\models;

use Codeception\Test\Unit;
use Yii;
use yii\console\Exception as ConsoleException;

class MigrationTest extends Unit
{
	
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
				//'migrationNamespaces' => [],
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
				'migrationNamespaces' => ['app\migrations'],
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
	
}