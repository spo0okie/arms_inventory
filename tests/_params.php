<?php
/** Адреса HTTP-наборов: отдельный сервер запускающего скрипта или локальный :8081. */
$serverUrl = rtrim(getenv('ARMS_TEST_SERVER_URL') ?: 'http://127.0.0.1:8081', '/');
return [
    'TEST_SERVER_URL' => $serverUrl,
    'TEST_ENTRY_URL' => $serverUrl . '/web/index-test.php',
];