<?php

namespace app\generation\generators;

class StringGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
		//если нужен пустой атрибут
		if ($params['empty']??false) {
			//если атрибут может быть null
			if ($params['nullable']??false) return null;
			
			return '';
		}

        $min = $params['min'] ?? 5;
        $max = $params['max'] ?? 20;

        $length = random_int($min, $max);

        return self::randomString($length);
    }

    public static function randomString(int $length): string
    {
        // максимально простой и быстрый вариант
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $result = '';
        $maxIndex = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $maxIndex)];
        }

        return $result;
    }
}