<?php

namespace app\generation\generators;

/**
 * Генератор для типа ntext (textarea - простой текст без форматирования)
 * При рендере nl конвертируется в br
 */
class NtextGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

        //генерируем многострочный текст
        $min = $params['min'] ?? 10;
        $max = $params['max'] ?? 50;

        $length = random_int($min, $max);
        
        return $this->randomMultilineText($length);
    }

    /**
     * Генерирует многострочный текст
     * @param int $lineCount количество строк
     * @return string
     */
    protected function randomMultilineText(int $lineCount): string
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
            $lines[] = implode(' ', $lineWords);
        }
        
        return implode("\n", $lines);
    }
}
