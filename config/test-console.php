<?php
//конфиг для консольного теста
use yii\helpers\ArrayHelper;

$dbTest = ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php',
	['dsn'=>'mysql:host=127.0.0.1;dbname=arms_test', 'username' => 'root',    'password' => '',]
);

/**
 * Application configuration shared by all test types
 */
$config=ArrayHelper::merge(	require __DIR__ . '/console.php',[
    'id' => 'arms-tests',
    'components' => [
		// основная тестовая БД
        'db' => $dbTest,
		// Конфиг для создания временных БД
		'db_root' => array_merge($dbTest,[
			'dsn' => 'mysql:host=127.0.0.1',
		]),
		/*'request' => [
			'cookieValidationKey' => 'test',
			'enableCsrfValidation' => false,
		],*/
	],
    'params' => ArrayHelper::merge(
		require __DIR__ . '/params.php',
		require __DIR__ . '/params-local.php'
	),
]);

return $config;
