<?php

namespace app\generation\generators;

/**
 * Генератор для типа string[] (массив строк)
 * Используется для API/генератора, не используется в UI
 */
class StringArrayGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return [];
        }

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        //количество записей в атрибуте
        $min = $params['min'] ?? 1;
        $max = $params['max'] ?? 5;
        $count = mt_rand($min, $max);

        
        $result = [];
        for ($i = 0; $i < $count; $i++) {            
            $result[] = StringGenerator::generate($params);
        }
        
        return $result;
    }

    
}
