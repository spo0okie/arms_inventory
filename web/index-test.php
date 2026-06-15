<?php

// NOTE: Make sure this file is not accessible when deployed to production
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// В acceptance-тестах config/params-test.php переписывается на лету (см. AuthorizationModesCest),
// а встроенный сервер php -S (SAPI cli-server) держит OPcache с revalidate_freq>0, поэтому
// до 2с отдаёт устаревшую скомпилированную версию параметров. Принудительно инвалидируем
// именно этот файл, чтобы каждый запрос видел актуальные authorizedView/useRBAC.
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__DIR__ . '/../config/params-test.php', true);
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/test-web.php';

(new yii\web\Application($config))->run();
