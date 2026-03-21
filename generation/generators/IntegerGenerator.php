<?php

namespace app\generation\generators;

/**
 * Генератор для типа integer (целые числа)
 */
class IntegerGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return 0;
        }

        //получаем границы значений
        $min = $params['min'] ?? 0;
        $max = $params['max'] ?? 1000;
        
        //генерируем случайное целое число
        return random_int($min, $max);
    }
}
