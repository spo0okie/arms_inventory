<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Rest extends \Codeception\Module
{
	public function _beforeSuite($settings = array())
	{
		//$params=require __DIR__.'/../_data/get-routes-data.php';
		Yii2::initFromFilename('test-web.php');
		codecept_debug('Initializing Suite DB...');
		//Подготавливаем временную БД
		Database::dropYiiDb();
		Database::prepareYiiDb();
		Database::loadSqlDump(__DIR__ . '/../../_data/arms_demo.sql');	}
	
	public static $testsFailed = false;
	
	public function _afterSuite()
	{
		//if (static::$testsFailed) return;
		// После завершения тестов удаляем тестовую БД
		//Yii2::initFromFilename('test-api.php');
		//Database::dropYiiDb();
	}
}
