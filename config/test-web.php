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
	['dsn'=>'mysql:host=127.0.0.1;dbname=arms_test', 'username' => 'root',    'password' => '',]
);

/**
 * Application configuration shared by all test types
 */
$config=\yii\helpers\ArrayHelper::merge(require __DIR__ . '/web.php',[
    'id' => 'arms-tests',
	'bootstrap' => ['debug'],
    'components' => [
        'db' => $testDb,
		'db_root' => array_merge($testDb,[
			'dsn' => 'mysql:host=127.0.0.1',
		]),
		'urlManager' => [
            'showScriptName' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
		'log' => [
			'traceLevel' => 5,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning', 'info', 'trace'],
					'categories' => ['application', 'yii\db\*'],
					'logFile' => '@runtime/logs/console.log',
				],
			],
		],
    ],
	'modules'=>[
		'debug'=>[
			'class' => 'yii\debug\Module',
			// uncomment the following to add your IP if you are not connecting from localhost.
			//'allowedIPs' => ['127.0.0.1', '::1'],
			// 'user'-панель на PHP 8.1+ дергает class_exists($this->filterModel) без проверки
			// на null (yii2-debug UserPanel::init()) — при авторизованном запросе это
			// deprecation-предупреждение, а т.к. выше error_reporting(E_ALL) не глушит
			// deprecated, Yii ErrorHandler превращает его в фатальный ErrorException прямо
			// во время bootstrap приложения (до того как появляется error-страница) — 500
			// вместо ожидаемого кода доступа. Ловится только на связке PHP 8.1+/CI
			// (AuthorizationModesCest после логина). Отключаем панель, т.к. она не нужна тестам.
			'panels' => ['user' => false],
		],
	],
    'params' => $testParams,
]);

\Yii::$classMap['yii\helpers\Url'] = dirname(__DIR__) . '/helpers/Url.php';

return $config;
