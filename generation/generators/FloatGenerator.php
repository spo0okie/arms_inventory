<?php

namespace app\generation\generators;

/**
 * Генератор для типа float
 */
class FloatGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return 0;
        }

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        //получаем границы значений
        $min = $params['min'] ?? 0;
        $max = $params['max'] ?? 100000;
        
        //генерируем случайное число
        return mt_rand($min, $max)/100;
    }
}
