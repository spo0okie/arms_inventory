<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Rest extends \Codeception\Module
{
	/**
	 * Инициализация БД и генерация тестовых данных перенесены в
	 * tests/rest/RestAccessCest::routesProvider(), который вызывается codeception-ом
	 * раньше _beforeSuite (на этапе enumeration dataProvider'ов). Если init'ить здесь,
	 * SQL dump перезатирает данные, засеянные ModelFactory в routesProvider,
	 * и все id в зарегистрированных сценариях становятся невалидными.
	 *
	 * Поэтому метод оставлен явным no-op (по аналогии с Helper\Acceptance).
	 */
	public function _beforeSuite($settings = array())
	{
	}

	/**
	 * Проверяет, что HTTP-код ответа REST-модуля попадает в диапазон [$min, $max].
	 * В текущей версии Codeception PhpBrowser/REST нет нативного seeResponseCodeIsBetween,
	 * поэтому раннер RestAccessCest использует этот хелпер.
	 */
	public function seeResponseCodeIsBetween(int $min, int $max, string $message = ''): void
	{
		/** @var \Codeception\Module\PhpBrowser $browser */
		$browser = $this->getModule('PhpBrowser');
		$status = $browser->client->getInternalResponse()->getStatusCode();
		\PHPUnit\Framework\Assert::assertGreaterThanOrEqual(
			$min,
			$status,
			$message !== '' ? $message : "response code $status is below min $min"
		);
		\PHPUnit\Framework\Assert::assertLessThanOrEqual(
			$max,
			$status,
			$message !== '' ? $message : "response code $status is above max $max"
		);
	}

	public static $testsFailed = false;

	public function _afterSuite()
	{
		//if (static::$testsFailed) return;
		// После завершения тестов удаляем тестовую БД
		//Yii2::initFromFilename('test-api.php');
		//Database::dropYiiDb();
	}
}
