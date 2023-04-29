<?php

use yii\helpers\ArrayHelper;

$db = ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php'
);

// test database! Important not to run tests on production or development databases
$db['dsn'] = 'mysql:host=127.0.0.1;dbname=arms_test';

return $db;
