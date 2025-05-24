<?php
//конфиг для консольного теста
use yii\helpers\ArrayHelper;

$db=ArrayHelper::merge(
	require __DIR__ . '/test_db.php',
	require __DIR__ . '/test_db-local.php'
);

/**
 * Application configuration shared by all test types
 */
$config=ArrayHelper::merge(	require __DIR__ . '/console.php',[
    'id' => 'arms-tests',
    'components' => [
		// основная тестовая БД
        'db' => $db,
		// Конфиг для создания временных БД
		'db_root' => array_merge($db,[
			'dsn' => 'mysql:host=127.0.0.1',
		]),
		'request' => [
			'cookieValidationKey' => 'test',
			'enableCsrfValidation' => false,
		],
	],
    'params' => ArrayHelper::merge(
		require __DIR__ . '/params.php',
		require __DIR__ . '/params-local.php'
	),
]);

return $config;
