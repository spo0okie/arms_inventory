<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$db['dsn'] = 'mysql:host=192.168.64.54;port=3307;dbname=arms_test';

return $db;
