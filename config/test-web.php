<?php

namespace app\config;

// Deprecated НЕ подавляем: тестовое окружение должно быть не мягче боевого,
// иначе full/empty-сценарии PageAccessCest не ловят депрекейты (strlen(null)
// и т.п.), которые на dev/prod роняют страницы в debug-режиме
error_reporting(E_ALL);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', true);

$testParams = \yii\helpers\ArrayHelper::merge(
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php',
	require __DIR__ . '/params-test.php'
);

$testDb = \yii\helpers\ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php',
	['dsn'=>'mysql:host=127.0.0.1;dbname=arms_test', 'username' => 'root',    'password' => '', 'enableSchemaCache' => false,]
);

/**
 * Application configuration shared by all test types
 */
$config=\yii\helpers\ArrayHelper::merge(require __DIR__ . '/web.php',[
    'id' => 'arms-tests',
    'components' => [
        'db' => $testDb,
		'db_root' => array_merge($testDb,[
			'dsn' => 'mysql:host=127.0.0.1',
		]),
		'urlManager' => [
            'showScriptName' => true,
        ],
        'request' => [
            // Codeception передаёт имя скрипта без web/: фиксируем путь для правильного @webroot.
            'scriptFile' => dirname(__DIR__) . '/web/index-test.php',
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
		'log' => [
			'traceLevel' => 0,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
					'categories' => ['application', 'yii\db\*'],
					'logFile' => '@runtime/logs/console.log',
				],
			],
		],
    ],
    'params' => $testParams,
]);

\Yii::$classMap['yii\helpers\Url'] = dirname(__DIR__) . '/helpers/Url.php';

// Toolbar и SQL backtrace не участвуют в проверках, но накапливают логи между тестами.
$config['bootstrap'] = array_values(array_diff($config['bootstrap'], ['debug']));
unset($config['modules']['debug']);
// CLI и HTTP не делят опубликованные файлы и PHP-сессии с рабочим Apache.
\yii\helpers\FileHelper::createDirectory(dirname(__DIR__) . '/web/assets-test');
\yii\helpers\FileHelper::createDirectory(dirname(__DIR__) . '/runtime/test-sessions');
$config['components']['assetManager']['basePath'] = dirname(__DIR__) . '/web/assets-test';
$config['components']['assetManager']['baseUrl'] = '/web/assets-test';
$config['components']['session']['savePath'] = dirname(__DIR__) . '/runtime/test-sessions';

return $config;
