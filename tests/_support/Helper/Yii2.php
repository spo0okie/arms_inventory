<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Yii2 extends \Codeception\Module
{
	public static function initFromConfig($settings)
	{
		new \yii\web\Application($settings);
	}
	
	public static function initFromFile($file)
	{
		codecept_debug('Initializing Yii2 application from config: ' . $file);
		static::initFromConfig(require $file);
	}

	public static function initFromFileName($filename)
	{
		static::initFromFile(__DIR__ . '/../../../config/' . $filename);
	}
}
