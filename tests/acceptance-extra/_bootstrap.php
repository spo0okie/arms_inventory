<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

codecept_debug('_bootstrap for extra acceptance tests started');

require_once __DIR__ .'/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../helpers/ArrayHelper.php';
require_once __DIR__ . '/../../helpers/StringHelper.php';

codecept_debug('Initializing Extra acceptance tests DB...');
Helper\Yii2::initFromFilename('test-web.php');
// Эти проверки читают фиксированные демо-ID; дамп сам пересоздаёт БД один раз.
Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');
codecept_debug('_bootstrap for extra acceptance tests finished');