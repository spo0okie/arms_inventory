<?php
//конфиг для теста миграций
use yii\helpers\ArrayHelper;

/**
 * Application configuration shared by all test types
 */
$config=ArrayHelper::merge(	require __DIR__ . '/test-console.php',[
    'id' => 'arms-tests-migrations',
    'components' => [
		// Конфиг для временной БД миграций
		'db' => [
			'dsn' => 'mysql:host=127.0.0.1;dbname=yii2_migrations_test',
		],
	],
]);

return $config;
