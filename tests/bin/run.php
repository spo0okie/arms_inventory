<?php
/** Запускает Codeception с отдельным локальным HTTP-сервером для всех наборов. */
$root = dirname(__DIR__, 2);
chdir($root);
$logDirectory = $root . '/tests/log';
if (!is_dir($logDirectory)) mkdir($logDirectory, 0777, true);
// Явный XDEBUG_MODE пользователя сохраняет возможность включить coverage/debug.
if (getenv('XDEBUG_MODE') === false) putenv('XDEBUG_MODE=off');
$server = null;
$exitCode = 1;
$started = microtime(true);
try {
    if (!getenv('ARMS_TEST_SERVER_URL')) {
        // ОС выбирает свободный порт: занятый Apache :8081 не мешает тестам.
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if ($socket === false) throw new RuntimeException($errorMessage, $errorCode);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $server = proc_open([PHP_BINARY, '-d', 'opcache.enable_cli=1', '-S', $address, '-t', $root], [
            0 => ['pipe', 'r'],
            1 => ['file', $logDirectory . '/web-server.log', 'w'],
            2 => ['file', $logDirectory . '/web-server.log', 'a'],
        ], $pipes, $root);
        if (!is_resource($server)) throw new RuntimeException('Не удалось запустить тестовый HTTP-сервер');
        fclose($pipes[0]);
        $ready = false;
        $port = (int)substr(strrchr($address, ':'), 1);
        $deadline = microtime(true) + 5;
        do {
            // Проверка TCP не обращается к БД до bootstrap тестового набора.
            $connection = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);
            if (is_resource($connection)) {
                fclose($connection);
                $ready = true;
                break;
            }
            if (!proc_get_status($server)['running']) break;
            usleep(50000);
        } while (microtime(true) < $deadline);
        if (!$ready) throw new RuntimeException('Тестовый HTTP-сервер не запустился: tests/log/web-server.log');
        putenv('ARMS_TEST_SERVER_URL=http://' . $address);
    }
    echo 'HTTP: ' . getenv('ARMS_TEST_SERVER_URL') . PHP_EOL;
    // Передаём аргументы как отдельные значения, без командной оболочки.
    $command = array_merge([PHP_BINARY, $root . '/vendor/bin/codecept', 'run'], array_slice($argv, 1));
    $tests = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
    if (!is_resource($tests)) throw new RuntimeException('Не удалось запустить Codeception');
    $exitCode = proc_close($tests);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
} finally {
    // Завершаем только созданный нами сервер; внешний сервер не затрагиваем.
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
    printf("Полное время запуска: %.3f с\n", microtime(true) - $started);
}
exit($exitCode);