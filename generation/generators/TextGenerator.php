<?php

namespace app\generation\generators;

/**
 * Генератор для типа text (текст с форматированием)
 * Как ntext, но с поддержкой форматирования
 */
class TextGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

		//генерируем текст с форматированием (простой HTML)
        $min = $params['min'] ?? 10;
        $max = $params['max'] ?? 50;

        $lineCount = mt_rand($min, $max);

        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $lines[] = self::randomWords();
        }

        return implode("\n", $lines);
    }

    /**
     * Возвращает случайный текст из Lorem-слов
     * @param int $minWords    минимальное количество слов
     * @param int $maxWords    максимальное количество слов
     * @return string
     */
    public static function randomWords($minWords = 3, $maxWords = 10, $formated=true): string
    {
		$wordCount = mt_rand($minWords, $maxWords);
		$words = [];
		for ($j = 0; $j < $wordCount; $j++) {
			$word=self::randomWord();
			if ($formated) $word=self::randomFormat($word);
			$words[]=$word;
		}
        return implode(' ',$words);
    }

    /**
     * Возвращает случайное Lorem-слово
     * @return string
     */
    public static function randomWord(): string
    {
        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
                  'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
                  'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua'];

        return $words[mt_rand(0, count($words) - 1)];
    }

    /**
     * Применяет случайное форматирование к строке
     * @param string $text
     * @return string
     */
    public static function randomFormat(string $text): string
    {
        switch (mt_rand(0, 3)) {
            case 0:
                return "<p>{$text}</p>";
            case 1:
                return "<strong>{$text}</strong>";
            case 2:
                return "<em>{$text}</em>";
            default:
                return $text;
        }
    }
}
