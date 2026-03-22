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

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        $min = $params['min'] ?? 5;
        $max = $params['max'] ?? 20;

        $length = mt_rand($min, $max);

        return self::randomString($length);
    }

    public static function randomString(int $length): string
    {
        // максимально простой и быстрый вариант
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $result = '';
        $maxIndex = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $maxIndex)];
        }

        return $result;
    }
}