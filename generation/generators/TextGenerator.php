<?php

namespace app\generation\generators;

/**
 * Генератор для типа text (текст с форматированием)
 * Как ntext, но с поддержкой форматирования
 */
class TextGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

        //генерируем текст с форматированием (простой HTML)
        $min = $params['min'] ?? 10;
        $max = $params['max'] ?? 50;

        $length = random_int($min, $max);
        
        return $this->randomFormattedText($length);
    }

    /**
     * Генерирует текст с простым форматированием (HTML)
     * @param int $lineCount количество строк
     * @return string
     */
    protected function randomFormattedText(int $lineCount): string
    {
        $lines = [];
        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 
                  'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
                  'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua'];
        
        for ($i = 0; $i < $lineCount; $i++) {
            $wordCount = random_int(3, 10);
            $lineWords = [];
            for ($j = 0; $j < $wordCount; $j++) {
                $lineWords[] = $words[random_int(0, count($words) - 1)];
            }
            $text = implode(' ', $lineWords);
            
            //добавляем простое форматирование
            $format = random_int(0, 3);
            switch ($format) {
                case 0:
                    $lines[] = "<p>{$text}</p>";
                    break;
                case 1:
                    $lines[] = "<strong>{$text}</strong>";
                    break;
                case 2:
                    $lines[] = "<em>{$text}</em>";
                    break;
                default:
                    $lines[] = $text;
            }
        }
        
        return implode("\n", $lines);
    }
}
