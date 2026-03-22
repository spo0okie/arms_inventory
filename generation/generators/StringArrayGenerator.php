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

        //генерируем массив строк
        //по умолчанию 1-5 элементов
        $count = $params['count'] ?? random_int(1, 5);
        
        $result = [];
        for ($i = 0; $i < $count; $i++) {            
            $result[] = StringGenerator::generate($params);
        }
        
        return $result;
    }

    
}
