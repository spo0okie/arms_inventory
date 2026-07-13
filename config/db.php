<?php

return [
    'class' => 'yii\db\Connection',
	'dsn' => 'mysql:host=127.0.0.1;dbname=arms', 'username' => 'root',    'password' => '',
	'on afterOpen' => function($event) {
        $event->sender->createCommand("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET time_zone='+00:00';set sql_mode='';")->execute();
	},
    'charset' => 'utf8mb4',

	//вне dev/test кэшируем схемы таблиц: без кэша каждый запрос перечитывает их заново
	//(SHOW FULL COLUMNS + SHOW CREATE TABLE + констрейнты на каждую задействованную таблицу).
	//YII_ENV_DEV: true на dev (index.php) и в тестах (test-web.php определяет явно,
	//т.к. тесты пересоздают БД и кэш схем им противопоказан); на проде - false.
	//После миграций кэш протухает сам (TTL) либо сбрасывается через `yii cache/flush-schema`
	'enableSchemaCache' => !YII_ENV_DEV,
	'schemaCacheDuration' => 3600,
	'schemaCache' => 'cache',
];
