<?php

namespace tests\unit;

use Codeception\Test\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Тесты качества кода проекта
 * Проверяет отсутствие BOM заголовков в PHP файлах
 * были проблемы при рендере выводе посторонних символов
 */
class CodeQualityTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Проверяет что в проекте нет PHP файлов с UTF-8 BOM заголовком
     * BOM может вызвать проблемы с вывод заголовков и сессиями
     */
    public function testNoBomHeadersInPhpFiles()
    {
        $projectRoot = dirname(dirname(__DIR__));
        $bomFiles = $this->findFilesWithBom($projectRoot);

        $this->assertEmpty(
            $bomFiles,
            sprintf(
                "Found %d PHP files with UTF-8 BOM header:\n%s\n\n" .
                "To fix, run:\n" .
                "\$bomFiles = @(); Get-ChildItem -Path '%s' -Recurse -Include '*.php' | " .
                "Where-Object { \$_.FullName -notmatch '\\\\vendor\\\\' } | " .
                "ForEach-Object { \$bytes = Get-Content \$_.FullName -AsByteStream -TotalCount 3; " .
                "if (\$bytes.Length -ge 3 -and \$bytes[0] -eq 0xEF -and \$bytes[1] -eq 0xBB -and \$bytes[2] -eq 0xBF) { \$bomFiles += \$_ } }; " .
                "\$bomFiles | ForEach-Object { \$content = Get-Content \$_.FullName -Encoding UTF8; " .
                "if (\$content.StartsWith((([char]0xFEFF)))) { \$content = \$content.Substring(1) }; " .
                "Set-Content \$_.FullName -Value \$content -Encoding UTF8NoBOM }",
                count($bomFiles),
                implode("\n", $bomFiles),
                $projectRoot
            )
        );
    }

    /**
     * Рекурсивно ищет все PHP файлы с UTF-8 BOM заголовком
     * Исключает папку vendor
     *
     * @param string $directory Корневая папка проекта
     * @return array Массив путей к файлам с BOM
     */
    protected function findFilesWithBom(string $directory): array
    {
        $bomFiles = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $phpFiles = new RegexIterator($iterator, '/\.php$/', RegexIterator::MATCH);

            foreach ($phpFiles as $file) {
                // Исключаем файлы из vendor
                if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
                    continue;
                }

                // Проверяем наличие BOM (EF BB BF в начале файла)
                $handle = fopen($file->getPathname(), 'rb');
                if ($handle === false) {
                    continue;
                }

                $bytes = fread($handle, 3);
                fclose($handle);

                if (strlen($bytes) >= 3 &&
                    ord($bytes[0]) === 0xEF &&
                    ord($bytes[1]) === 0xBB &&
                    ord($bytes[2]) === 0xBF) {
                    // Храним относительный путь для удобства чтения
                    $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $bomFiles[] = $relativePath;
                }
            }
        } catch (\Exception $e) {
            $this->fail("Error while scanning project directory: " . $e->getMessage());
        }

        sort($bomFiles);
        return $bomFiles;
    }
}
