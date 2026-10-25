<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

codecept_debug('_bootstrap for unit tests started');

require_once __DIR__ .'/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../helpers/ArrayHelper.php';
require_once __DIR__ . '/../../helpers/StringHelper.php';

// Схема и демо-справочники нужны до data provider; CRUD-тесты создают свои модели.
Helper\Yii2::initFromFileName('test-console.php');
Helper\Database::loadSqlDump();
