<?php

use yii\helpers\ArrayHelper;

$params = ArrayHelper::merge(
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);
$db = ArrayHelper::merge(
	require __DIR__ . '/test_db.php',
	require __DIR__ . '/test_db-local.php'
);

/**
 * Application configuration shared by all test types
 */
$config=ArrayHelper::merge(	require __DIR__ . '/web.php',[
    'id' => 'arms-tests',
    'components' => [
        'db' => $db,
        'urlManager' => [
            'showScriptName' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
    ],
    'params' => $params,
]);

Yii::$classMap['yii\helpers\Url'] = dirname(__DIR__) . '/helpers/Url.php';

return $config;
