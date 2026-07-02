<?php

return [
    'class' => 'yii\db\Connection',
	'dsn' => 'mysql:host=127.0.0.1;dbname=arms', 'username' => 'root',    'password' => '',
	'on afterOpen' => function($event) {
        $event->sender->createCommand("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET time_zone='+00:00';set sql_mode='';")->execute();
	},
    'charset' => 'utf8mb4',
];
