<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

codecept_debug('_bootstrap for acceptance tests started');

require_once __DIR__ . '/../../helpers/ArrayHelper.php';
require_once __DIR__ . '/../../helpers/StringHelper.php';
require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../vendor/autoload.php';

