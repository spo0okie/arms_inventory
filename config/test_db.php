<?php

use yii\helpers\ArrayHelper;

$db = ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php',
	['dsn'=>'mysql:host=127.0.0.1;dbname=arms_demo', 'username' => 'root',    'password' => '',]
);

return $db;
