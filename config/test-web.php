<?php

namespace app\config;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', true);

$testParams = \yii\helpers\ArrayHelper::merge(
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
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
		],
	],
    'params' => $testParams,
]);

\Yii::$classMap['yii\helpers\Url'] = dirname(__DIR__) . '/helpers/Url.php';

return $config;
