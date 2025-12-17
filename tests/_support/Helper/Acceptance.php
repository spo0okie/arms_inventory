<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
	public function _beforeSuite($settings = array())
	{
		/*
		Перенесено в PagesAccessCest->routesProvider,
		так как он вызывается раньше этого метода
		*/
	}
	
	public static $testsFailed = false;
	
	public function _afterSuite()
	{
		//if (static::$testsFailed) return;
		// После завершения тестов удаляем тестовую БД
		//Yii2::initFromFilename('test-web.php');
		//Database::dropYiiDb();
	}
}
