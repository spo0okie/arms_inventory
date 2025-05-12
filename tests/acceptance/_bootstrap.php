<?php

//Подгружаем наше Yii2 приложение для тестов, т.к. нам нужны хелперы, а также генераторы тестовых запросов

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
	require_once __DIR__ . '/../../vendor/autoload.php';
	require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
	$config = require __DIR__ . '/../../config/test.php';
	new yii\web\Application($config);
}