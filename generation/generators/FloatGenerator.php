<?php

namespace app\generation\generators;

/**
 * Генератор для типа number (числа)
 */
class NumberGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return 0;
        }

        //получаем границы значений
        $min = $params['min'] ?? 0;
        $max = $params['max'] ?? 100000;
        
        //генерируем случайное число
        return random_int($min, $max)/100;
    }
}
