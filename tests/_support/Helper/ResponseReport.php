<?php
namespace Helper;

use Codeception\Event\PrintResultEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\TestInterface;
use Codeception\Test\Descriptor;

/** Оставляет большие ответы в файлах, не форматируя мегабайты HTML в консоли. */
class ResponseReport extends Extension
{
    /** Обрабатываем артефакты до стандартного консольного отчёта. */
    public static array $events = [
        Events::RESULT_PRINT_AFTER => ['compactResponses', 100],
    ];

    /** Заменяет большой body ссылкой на полный ответ, сохраняя диагностику падения. */
    public function compactResponses(PrintResultEvent $event): void
    {
        if (!empty($this->options['no-artifacts'])) return;
        $result = $event->getResult();
        foreach (array_merge($result->errors(), $result->failures()) as $failure) {
            $test = $failure->getTest();
            if (!$test instanceof TestInterface) continue;
            $metadata = $test->getMetadata();
            $reports = $metadata->getReports();
            if (!is_string($reports['body'] ?? null) || strlen($reports['body']) <= 4096) continue;

            // PhpBrowser обычно уже сохранил полный HTML/JSON при падении теста.
            $path = $reports['response'] ?? null;
            if (!is_string($path) || !is_file($path) || @file_get_contents($path) !== $reports['body']) {
                $path = codecept_output_dir() . 'response-' . sha1(Descriptor::getTestFullName($test) . $test->toString()) . '.txt';
                // Если файл не удалось записать, оставляем исходное тело в отчёте.
                if (@file_put_contents($path, $reports['body']) === false) continue;
            }
            $metadata->addReport('body', $path);
        }
    }
}