<?php

use yii\helpers\ArrayHelper;

$params = ArrayHelper::merge(
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);
$db = ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php'
);

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\console\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
	    'authManager' => [
		    'class' => 'yii\rbac\DbManager',
	    ],
		'errorHandler'=>[
			'class'=>'app\console\ErrorHandler',
		],
		'user' => [
			'class' => 'app\models\Users',
			'identityClass' => 'app\models\Users',
		],
    ],
    'params' => $params,
	'controllerMap' => [
		'migrate' => [
			'class' => 'yii\console\controllers\MigrateController',
			'migrationNamespaces' => [
				//'app\migrations\svc', // Служебные/сервисные миграции
				'app\migrations', // Common migrations for the whole application
				//'module\migrations', // Migrations for the specific project's module
				//'some\extension\migrations', // Migrations for the specific extension
			],
			'migrationPath'=>null,
		],
	],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
