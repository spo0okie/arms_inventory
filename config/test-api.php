<?php
//конфиг для acceptance теста
use yii\helpers\ArrayHelper;

/**
 * Application configuration shared by all test types
 */
$config=ArrayHelper::merge(	require __DIR__ . '/test-web.php',[
    'id' => 'arms-tests-crud',
    'components' => [
		// Конфиг для временной БД для тестирования crud операций
		'db' => [
			'dsn' => 'mysql:host=127.0.0.1;dbname=arms_test_rest',
		],
	],
]);

return $config;
